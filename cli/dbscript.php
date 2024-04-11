<?php
if (php_sapi_name() !== 'cli') {
    die("This script can only be executed under CLI.");
}
require_once 'config.php';

$directory = $CFG->dirroot . '/dbscript/' . $CFG->dbtype . '/';
$filename = 'script_' . time() . '.php';

$file = fopen($directory . $filename, 'w');
$content = "<?php\n";
$content .= "global \$DB;\n";
fwrite($file, $content);
fclose($file);

echo "Successfully created file $filename";