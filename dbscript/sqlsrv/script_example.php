<?php
// You must rename or create a new file named in the format: script_unixtime.php
// For example, my unixtime is 1712806913, then my file name is script_1712806913.php
// Example code here
global $DB;
if(!$DB->table_exists('categories')) {
    $columns = [
        $DB->create_column_script('id', DB_TYPE_BIGINT, '', true, true, true),
        $DB->create_column_script('name', DB_TYPE_CHAR, '255', true),
        $DB->create_column_script('code', DB_TYPE_CHAR, '255', true),
        $DB->create_column_script('timecreated', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('timemodified', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('usercreated', DB_TYPE_BIGINT, '', true),
        $DB->create_column_script('usermodified', DB_TYPE_BIGINT, '', true)
    ];
    $DB->create_table('categories', $columns);
}