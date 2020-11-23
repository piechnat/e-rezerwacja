<?php

namespace App\CustomTypes;

use Exception;

class ReservationConflictException extends Exception
{
    public function __construct($message, $value)
    {
        parent::__construct($message, $value, null);
    }
}
