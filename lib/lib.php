<?php
function custom_handler_exception(Exception $ex) {
    $message = $ex->getMessage();
    $errorcode = $ex->getCode();
    $line = $ex->getLine();
    $file = $ex->getFile();
    $tracing = $ex->getTrace();
    if(ERROR_DISPLAY !== '1') {
        echo $message . "<br>";
        return;
    }
    echo '<div style="margin-left: 10px">';
    echo 'An exception was thrown in file <strong>' . $file . '</strong> line <strong>' . $line . '</strong>: '; 
    echo $message . "<br>";
    echo '<strong>Error code</strong>: ' . $errorcode . "<br>";
    if(!empty($ex->errors)) {
        echo '<strong>Errors</strong>: <br>';
        foreach($ex->errors as $error) {
            echo '- ' . $error['message'] . '<br>';
        }
    }
    echo '<strong>Tracing</strong>:<br>';
    foreach($tracing as $trace) {
        $tracemessage = '- ';
        if(empty($trace['line'])) {
            $trace['line'] = '?';
        }
        if(empty($trace['file'])) {
            $trace['file'] = 'unknowfile';
        }
        $tracemessage .= 'Line ' . $trace['line'] . ' of ' . $trace['file'];
        if(!empty($trace['function'])) {
            $tracemessage .= ': call to ';
            if(!empty($trace['class'])) {
                $tracemessage .= $trace['class'] . $trace['type'];
            }
            $tracemessage .= $trace['function'] . '()<br>';
        }
        echo $tracemessage;
    }
    echo '</div>';
}

function custom_handler_error($errno, $errstr, $errfile, $errline) {
    echo "<b>Error:</b> [$errno] $errstr<br>";
    echo "File: $errfile, Line: $errline<br>";
    return false;
}