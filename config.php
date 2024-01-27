<?php

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/vendor/autoload.php';

global $CFG;
$CFG = new stdClass;
$CFG->dirroot = __DIR__;
$CFG->libdir = $CFG->dirroot . '/lib';
$CFG->dbtype = DB_TYPE;
$CFG->dbhost = DB_HOST;
$CFG->dbport = DB_PORT;
$CFG->dbname = DB_DATABASE;
$CFG->dbusername = DB_USERNAME;
$CFG->dbpassword = DB_PASSWORD;

require_once $CFG->dirroot . '/lib/setup.php';