<?php

namespace App\CustomTypes;

use Exception;

class ReservationNotAllowedException extends Exception
{
    public function __construct($message, $value = 0)
    {
        parent::__construct($message, $value, null);
    }
}
