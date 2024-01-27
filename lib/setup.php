<?php
use database\sqlsrv_database;

global $CFG, $DB;

require_once $CFG->libdir . '/lib.php';

if(ERROR_DISPLAY === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

set_exception_handler('custom_handler_exception');

$DB = new sqlsrv_database();