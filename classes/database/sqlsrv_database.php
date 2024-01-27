<?php
namespace database;
use database\database;
use exceptions\DatabaseException;

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
        $result = sqlsrv_query($this->sqlsrv, $sql, $params);
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
        $this->do_query($sql);
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