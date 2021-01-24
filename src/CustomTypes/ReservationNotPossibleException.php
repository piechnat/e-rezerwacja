<?php

namespace App\CustomTypes;

use Exception;

class ReservationNotPossibleException extends Exception
{
    public function __construct($message, $value = 0)
    {
        parent::__construct($message, $value, null);
    }
}
