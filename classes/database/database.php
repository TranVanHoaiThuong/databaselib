<?php
namespace database;

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
     * Get single row
     * @param string $table Table name
     * @param array $params Condition in WHERE clause
     * @param string $field Field to select
     * @param string $sort Order by clause
     * @return array|bool
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
}