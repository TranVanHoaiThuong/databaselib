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

    public function get_database_type() {
        return $this->dbtype;
    }

    public function get_database_host() {
        return $this->dbtype;
    }

    public function get_database_script_path() {
        return $this->scriptpath;
    }

    abstract protected function connect();

    abstract protected function close();

    abstract protected function do_query($sql, $params = []);

    abstract public function run_script_database();

    abstract public function need_run_database_script() : bool;

    abstract public function create_column_script($name, $type, $length = '', $notnull = false, $isprimay = false, $identity = false, $default = false);

    abstract public function get_row_data($table, $params, $fields = '*');

    abstract public function get_rows_data($table, $params = [], $fields = '*', $sort = '');
}