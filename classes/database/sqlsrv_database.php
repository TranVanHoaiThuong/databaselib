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

    protected function do_query($sql, $params = []) {
        try {
            $result = sqlsrv_query($this->sqlsrv, $sql, $params);
        } catch (\Throwable $th) {
            throw $th;
        }
        if(!$result) {
            throw new DatabaseException("<strong>Failed on query</strong>: $sql", sqlsrv_errors());
        }
        return $result;
    }

    public function create_table($table, $columns) {
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

    public function table_exists($table) {
        $sql = "SELECT COUNT(*) as table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
        $stmtcheck = $this->do_query($sql, [$table]);
        if(sqlsrv_fetch_array($stmtcheck)['table_count'] > 0) {
            $this->free_stmt($stmtcheck);
            return true;
        }
        return false;
    }

    public function get_row_data($table, $params, $fields = '*', $sort = '') {
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

    public function get_rows_data($table, $params = [], $fields = '*', $sort = '') {
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

    public function insert_row($table, $objectdata, $returnid = true): int|bool {
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

    public function create_column_script($name, $type, $length = '', $notnull = false, $isprimay = false, $identity = false, $default = false) {
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