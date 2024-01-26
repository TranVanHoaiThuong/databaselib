<?php

use database\sqlsrv_database;

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/vendor/autoload.php';

global $CFG;
$CFG = new stdClass;
$CFG->dirroot = __DIR__;
$CFG->dbtype = DB_TYPE;
$CFG->dbhost = DB_HOST;
$CFG->dbport = DB_PORT;
$CFG->dbname = DB_DATABASE;
$CFG->dbusername = DB_USERNAME;
$CFG->dbpassword = DB_PASSWORD;

if(ERROR_DISPLAY === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if($CFG->dbhost !== 'dbhost') {
    global $DB;
    $DB = new sqlsrv_database();
} else {
    throw new Exception("Bạn bắt buộc phải cấu hình kết nối đến DB trong file env.php");
}