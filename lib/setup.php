<?php
use database\sqlsrv_database;
use exceptions\CustomException;

global $CFG, $DB;

require_once $CFG->libdir . '/lib.php';

if(ERROR_DISPLAY === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

set_error_handler('custom_handler_error', E_ALL | E_STRICT);
set_exception_handler('custom_handler_exception');

$DB = new sqlsrv_database();

$dblib = $CFG->libdir . '/' . DB_TYPE . '_dblib.php';
if(!file_exists($dblib)) {
    throw new CustomException('Could not find a dblib for database type ' . DB_TYPE);
}
require_once $dblib;

// Lần đầu chạy sẽ tự động sinh ra bảng
if(!$DB->table_exists('run_script_database_history')) {
    $columns = [
        $DB->create_column_script('id', DB_TYPE_BIGINT, '', true, true, true),
        $DB->create_column_script('filename', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('timestart', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('timeend', DB_TYPE_BIGINT, '', true)
    ];
    $DB->create_table('run_script_database_history', $columns);
}

if(!$DB->table_exists('users')) {
    $columns = [
        $DB->create_column_script('id', DB_TYPE_BIGINT, '', true, true, true),
        $DB->create_column_script('firstname', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('lastname', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('fullname', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('username', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('password', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('email', DB_TYPE_CHAR, 255, true),
        $DB->create_column_script('timecreated', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('timemodified', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('usercreated', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('usermodified', DB_TYPE_BIGINT, '', true)
    ];
    $DB->create_table('users', $columns);
}
// Check and run script db
$DB->run_script_database();