<?php
namespace database;

use exceptions\DatabaseException;
abstract class database {
    protected $dbtype;
    protected $dbhost;
    protected $dbname;
    protected $dbusername;
    protected $dbpassword;
    protected $dbport;
    protected $scriptpath;

    public function __construct($config) {
        $this->dbtype = $config->dbtype;
        $this->dbhost = $config->dbhost;
        $this->dbname = $config->dbname;
        $this->dbusername = $config->dbusername;
        $this->dbpassword = $config->dbpassword;
        $this->dbport = $config->dbport;
        $this->scriptpath = $config->dirroot . '/dbscript/' . $this->dbtype;
    }

    /**
     * Get DB type
     * @return string
     */
    public function get_database_type() {
        return $this->dbtype;
    }

    /**
     * Get DB host
     * @return string
     */
    public function get_database_host() {
        return $this->dbtype;
    }

    /**
     * Get the path to the script DB files
     * @return string
     */
    public function get_database_script_path() {
        return $this->scriptpath;
    }

    /**
     * Get all script file need run
     * @return array
     */
    protected function get_script_file_need_run() {
        $scripts = [];
        $scriptpath = $this->get_database_script_path();
        // Get all script was ran
        $oldscript = $this->get_rows_data('run_script_database_history', [], 'filename');
        $oldscript = array_map(function($old) {
            return $old->filename;
        }, $oldscript);
        // Start scan new script
        $files = scandir($scriptpath);
        $files = array_values(array_diff($files, ['.', '..']));
        foreach($files as $file) {
            if(in_array($file, $oldscript)) {
                continue;
            }
            if(!preg_match('/^script_\d{10}\.php$/', $file)) {
                continue;
            }
            $scripts[] = $file;
        }
        return $scripts;
    }

    /**
     * Connect DB
     */
    abstract protected function connect();

    /**
     * Close connect DB
     */
    abstract protected function close();

    /**
     * Free statement
     */
    abstract protected function free_stmt($stmt);

    /**
     * Execute query
     * @param string $sql Query string
     * @param array $params Query param
     */
    abstract protected function do_query(string $sql, array $params = []);

    /**
     * Execute script DB file
     */
    abstract public function run_script_database();

    /**
     * Check a table is exists
     */
    abstract public function table_exists(string $table): bool;

    /**
     * Create a script add column in create table statement
     * @param string $name Column name
     * @param string $type Column type
     * @param string $length Data length
     * @param bool $notnull Not allow null?
     * @param bool $isprimay Is primary key?
     * @param bool $identity Is identity?
     * @param mixed $default Default value
     * @return string
     */
    abstract public function create_column_script(string $name, string $type, string $length = '', $notnull = false, $isprimay = false, $identity = false, $default = false): string;

    /**
     * Create a table
     * @param string $table Table name
     * @param array $columns List column
     */
    abstract public function create_table(string $table, array $columns);

    /**
     * Add new column to exists table
     * @param string $table Table name
     * @param string $column Column name
     * @return bool
     */
    abstract public function column_exists(string $table, string $column): bool;

    /**
     * Add new column to exists table
     * @param string $table Table name
     * @param string $column Create from method create_column_script
     */
    abstract public function add_column(string $table, string $column);

    /**
     * Rename column in table. Be carefull with this action
     * @param string $table Table name
     * @param string $oldname Current name of column need to rename
     * @param string $newname New name of column need to rename
     */
    abstract public function rename_column(string $table, string $oldname, string $newname);

    /**
     * Change type of column
     * @param string $table Table name
     * @param string $column Create from method create_column_script
     */
    abstract public function change_column_type(string $table, string $column);

    /**
     * Drop a column. Be carefull with this action
     * @param string $table Table name
     * @param string $column Column need to drop
     */
    abstract public function drop_column(string $table, string $column);
    
    /**
     * Get single row
     * @param string $table Table name
     * @param array $params Condition in WHERE clause
     * @param string $field Field to select
     * @param string $sort Order by clause
     * @return object|bool
     */
    abstract public function get_row_data(string $table, array $params = [], string $fields = '*', string $sort = ''): object|bool;

    /**
     * Get multi row
     * @param string $table Table name
     * @param array $params Condition in WHERE clause
     * @param string $field Field to select
     * @param string $sort Order by clause
     * @return array|bool
     */
    abstract public function get_rows_data(string $table, array $params = [], string $fields = '*', string $sort = ''): array|bool;

    /**
     * Insert a row
     * @param string $table Table name
     * @param object $objectdata Data to insert
     * @param bool $returnid
     * @return int|bool
     */
    abstract public function insert_row(string $table, object $objectdata, bool $returnid): int|bool;

    /**
     * Update a row
     * @param string $table Table name
     * @param object $objectdata Need param id in object
     * @return bool
     */
    abstract public function update_row(string $table, object $objectdata): bool;

    /**
     * Delete rows in table
     * @param string $table Table name
     * @param array $conditions Condition to delete, it cannot be empty
     * @return bool
     */
    abstract public function delete_rows(string $table, array $conditions);

    /**
     * Get multi row with sql
     * @param string $sql Query
     * @param array $params Param in query
     * @return array|bool
     */
    abstract public function get_rows_data_sql(string $sql, array $params = []): array|bool;

    /**
     * Insert multi row
     * @param string $table Table name
     * @param array $rows Array of row to insert
     * @param bool $returnid
     * @return int|bool
     */
    public function insert_rows(string $table, array $rows, bool $returnid = true): array|bool {
        if(empty($rows)) {
            throw new DatabaseException('Insert data can not be empty!');
        }
        $return = [];
        foreach($rows as $row) {
            if(is_array($row)) {
                $row = (object)$row;
            }
            unset($row->id);
            $return[] = $this->insert_row($table, $row, $returnid);
        }
        return $returnid ? $return : true;
    }

    /**
     * Add new multi column to exists table
     * @param string $table Table name
     * @param array $column Array of columns is created from method create_column_script
     */
    public function add_columns(string $table, array $columns) {
        if(empty($columns)) {
            throw new DatabaseException('Columns can not be empty!');
        }
        foreach($columns as $column) {
            $this->add_column($table, $column);
        }
    }
}