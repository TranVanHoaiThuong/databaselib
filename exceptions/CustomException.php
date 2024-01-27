<?php
namespace exceptions;

use Exception;

class CustomException extends Exception {
    public $message;
    public array $errors;
    public function __construct($message, $errors = []) {
        $this->message = $message;
        $this->errors = $errors;
        parent::__construct($message, 0);
    }
}