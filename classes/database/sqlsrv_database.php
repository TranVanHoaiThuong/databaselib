<?php
namespace database;
use database\database;
use exceptions\DatabaseException;
use stdClass;

class sqlsrv_database extends database {
    protected $sqlsrv;

    public function __construct() {
        global $CFG;
        parent::__construct($CFG);
        $this->connect();
    }

    protected function connect() {
        if($this->dbport) {
            $this->dbhost = $this->dbhost . ',' . $this->dbport;
        }
        $connectoptions = [
            "Database" => $this->dbname,
            "Uid" => $this->dbusername,
            "PWD" => $this->dbpassword,
            "CharacterSet" => 'UTF-8'
        ];
        $this->sqlsrv = sqlsrv_connect($this->dbhost, $connectoptions);
        if(!$this->sqlsrv) {
            throw new DatabaseException("Cannot connect to database", sqlsrv_errors());
        }
    }

    protected function do_query(string $sql, array $params = []) {
        $result = sqlsrv_query($this->sqlsrv, $sql, $params);
        if(!$result) {
            throw new DatabaseException("<strong>Failed on query</strong>: $sql", sqlsrv_errors());
        }
        return $result;
    }

    public function create_table(string $table, array $columns) {
        if($this->table_exists($table)) {
            throw new DatabaseException("Table $table already exists");
        }
        if(!is_array($columns)) {
            throw new DatabaseException("Columns must be array");
        }
        if(empty($columns)) {
            throw new DatabaseException("Columns in table is empty");
        }
        $columns = implode(',' . PHP_EOL, $columns);
        $sql = "CREATE TABLE $table ($columns)";
        $doquery = $this->do_query($sql);
        $this->free_stmt($doquery);
    }

    public function table_exists(string $table): bool {
        $sql = "SELECT COUNT(*) as table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
        $stmtcheck = $this->do_query($sql, [$table]);
        if(sqlsrv_fetch_array($stmtcheck)['table_count'] > 0) {
            $this->free_stmt($stmtcheck);
            return true;
        }
        return false;
    }

    public function column_exists(string $table, string $column): bool {
        if(!$this->table_exists($table)) {
            return false;
        }
        $sql = "SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
        $checkcolumn = $this->do_query($sql, [$table, $column]);
        if(sqlsrv_has_rows($checkcolumn)) {
            $this->free_stmt($checkcolumn);
            return true;
        }
        $this->free_stmt($checkcolumn);
        return false;
    }

    public function add_column(string $table, string $column) {
        $sql = "ALTER TABLE $table
                ADD $column";
        $addcolumn = $this->do_query($sql);
        $this->free_stmt($addcolumn);
    }

    public function rename_column(string $table, string $oldname, string $newname) {
        if(!$this->column_exists($table, $oldname)) {
            throw new DatabaseException("Column $oldname not exists in table $table to rename");
        }
        sqlsrv_begin_transaction($this->sqlsrv);
        try {
            $sql = "EXEC sp_rename ?,  ?, 'COLUMN'";
            $stmt = sqlsrv_prepare($this->sqlsrv, $sql, [$table . '.' . $oldname, $newname]);
            sqlsrv_execute($stmt);
            sqlsrv_commit($this->sqlsrv);
            $this->free_stmt($stmt);
        } catch (\Throwable $th) {
            sqlsrv_rollback($this->sqlsrv);
            throw $th;
        }
    }

    public function change_column_type(string $table, string $column) {
        $sql = "ALTER TABLE $table
                ALTER COLUMN $column";
        $changetype = $this->do_query($sql);
        $this->free_stmt($changetype);
    }

    public function drop_column(string $table, string $column) {
        $sql = "ALTER TABLE $table
                DROP COLUMN $column";
        $dropcol = $this->do_query($sql);
        $this->free_stmt($dropcol);
    }

    public function run_script_database() {
        $filescripts = $this->get_script_file_need_run();
        if(!$filescripts) {
            return;
        }
        $scriptpath = $this->get_database_script_path();
        foreach($filescripts as $script) {
            $destination = $scriptpath . '/' . $script;
            if(!file_exists($destination)) {
                continue;
            }
            $start = time();
            require $destination;
            $end = time();
            $history = new stdClass;
            $history->filename = $script;
            $history->timestart = $start;
            $history->timeend = $end;
            $this->insert_row('run_script_database_history', $history);
        }
    }

    public function create_column_script(string $name, string $type, string $length = '', $notnull = false, $isprimay = false, $identity = false, $default = false): string {
        $script = $name . ' ' . $type;
        if($length && !in_array($type, [DB_TYPE_INT, DB_TYPE_BIGINT, DB_TYPE_SMALLINT, DB_TYPE_TINYINT, DB_TYPE_TEXT])) {
            switch($type) {
                case DB_TYPE_FLOAT:
                    break;
                case DB_TYPE_DECIMAL:
                    $length = explode(',', $length);
                    $script .= '(' . $length[0];
                    $script .= (!empty($length[1])) ? ', ' . $length[1] : ', 0';
                    $script .= ')';
                    break;
                default:
                    $script .= '(' . $length . ')';
                    break;
            }
        }
        if($notnull) {
            $script .= ' NOT NULL';
        }
        if($isprimay) {
            $script .= ' PRIMARY KEY';
        }
        if($identity) {
            $script .= ' IDENTITY(1,1)';
        }
        if(!is_bool($default)) {
            if(in_array($type, [DB_TYPE_CHAR, DB_TYPE_TEXT])) {
                $defaultvalue = "'$default'";
            } else {
                $defaultvalue = $default;
            }
            $script .= ' DEFAULT ' . $defaultvalue;
        }
        return $script;
    }

    public function get_row_data(string $table, array $params = [], string $fields = '*', string $sort = ''): object|bool {
        if(!is_array($params)) {
            throw new DatabaseException('Param $params must be an array!');
        }
        $sql = "SELECT $fields FROM $table";
        $where = [];
        foreach($params as $param => $value) {
            if(is_numeric($value)) {
                $where[] = $param . ' = ' . $value;
                continue;
            }
            $where[] = $param . " = N'$value'";
        }
        if(!empty($where)) {
            $where = implode(" AND ", $where);
            $sql .= " WHERE $where";
        }
        if($sort) {
            $sql .= " ORDER BY $sort";
        }
        $doquery = $this->do_query($sql);
        if($result = sqlsrv_fetch_array($doquery, SQLSRV_FETCH_ASSOC)) {
            $this->free_stmt($doquery);
            return (object)$result;
        }
        return false;
    }

    public function get_rows_data(string $table, array $params = [], string $fields = '*', string $sort = ''): array|bool {
        $sql = "SELECT $fields FROM $table";
        $where = [];
        foreach($params as $param => $value) {
            if(is_numeric($value)) {
                $where[] = $param . ' = ' . $value;
                continue;
            }
            $where[] = $param . " = N'$value'";
        }
        if(!empty($where)) {
            $where = implode(" AND ", $where);
            $sql .= " WHERE $where";
        }
        if($sort) {
            $sql .= " ORDER BY $sort";
        }
        $doquery = $this->do_query($sql);
        if($doquery) {
            $data = [];
            while($row = sqlsrv_fetch_array($doquery, SQLSRV_FETCH_ASSOC)) {
                $data[] = (object)$row;
            }
            $this->free_stmt($doquery);
            return $data;
        }
        return false;
    }

    public function insert_row(string $table, object $objectdata, $returnid = true): int|bool {
        $fields = [];
        $values = [];
        $qms = [];
        foreach($objectdata as $key => $value) {
            $fields[] = $key;
            $values[] = $value;
            $qms[] = '?';
        }

        $fields = implode(', ', $fields);
        $qms = implode(', ', $qms);
        $sql = "INSERT INTO $table ($fields) VALUES($qms)";
        $doinsert = $this->do_query($sql, $values);
        $this->free_stmt($doinsert);
        if($returnid) {
            return $this->get_row_data($table, [], 'TOP 1 id', 'id DESC')->id;
        }
        return true;
    }

    public function update_row(string $table, object $objectdata): bool {
        if(!isset($objectdata->id) || (isset($objectdata->id) && empty($objectdata->id))) {
            throw new DatabaseException('Data object must be have a id to update');
        }
        if(!is_numeric($objectdata->id)) {
            throw new DatabaseException('Param id must be a number');
        }
        $id = $objectdata->id;
        unset($objectdata->id);
        $set = [];
        foreach($objectdata as $key => $value) {
            if(is_numeric($value)) {
                $set[] = $key . ' = ' . $value;
                continue;
            }
            $set[] = $key . " = N'" . $value . "'";
        }
        if(empty($set)) {
            throw new DatabaseException('Data to update can not be empty');
        }
        $set = implode(',', $set);
        $sql = "UPDATE $table SET $set WHERE id = ?";
        $doupdate = $this->do_query($sql, [$id]);
        $this->free_stmt($doupdate);
        return true;
    }

    public function delete_rows(string $table, array $conditions) {
        if(empty($conditions)) {
            throw new DatabaseException('Condition can not be empty');
        }
        $where = [];
        $params = [];
        foreach($conditions as $column => $value) {
            $where[] = "$column = ?";
            $params[] = $value;
        }
        $sql = "DELETE $table WHERE " . implode(" AND ", $where);
        $dodelete = $this->do_query($sql, $params);
        $this->free_stmt($dodelete);
    }

    public function get_rows_data_sql(string $sql, array $params = []): array|bool {
        if (strpos(strtoupper($sql), 'SELECT') !== 0) {
            throw new DatabaseException('Query must be start with SELECT');
        }
        $doquery = $this->do_query($sql, $params);
        if($doquery) {
            $data = [];
            while($row = sqlsrv_fetch_array($doquery, SQLSRV_FETCH_ASSOC)) {
                $data[] = (object)$row;
            }
            $this->free_stmt($doquery);
            return $data;
        }
        return false;
    }

    protected function free_stmt($stmt) {
        sqlsrv_free_stmt($stmt);
    }

    protected function close() {
        if($this->sqlsrv) {
            sqlsrv_close($this->sqlsrv);
        }
    }

    public function __destruct() {
        $this->close();
    }
}