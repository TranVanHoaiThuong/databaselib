<?php
namespace exceptions;

use stdClass;

class DatabaseException extends CustomException {
    public function __construct($message, $errors = []) {
        parent::__construct($message, $errors);
    }
}