<?php
namespace database;

abstract class database {
    protected $dbtype;
    protected $dbhost;
    protected $dbname;
    protected $dbusername;
    protected $dbpassword;
    protected $dbport;

    public function __construct($config) {
        $this->dbtype = $config->dbtype;
        $this->dbhost = $config->dbhost;
        $this->dbname = $config->dbname;
        $this->dbusername = $config->dbusername;
        $this->dbpassword = $config->dbpassword;
        $this->dbport = $config->dbport;
    }

    abstract protected function connect();

    abstract protected function close();

    abstract protected function do_query($sql, $params = []);
}