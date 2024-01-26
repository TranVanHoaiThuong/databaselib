<?php
namespace exceptions;

use Exception;
use Throwable;

class DatabaseException extends Exception {
    public function __construct($message, $code = 0, Throwable $previous = null, $errors = []) {
        foreach($errors as $error) {
            echo "Error: " . $error['message'] . "<br>";  
        }
        echo $message . "<br>";
    }
}