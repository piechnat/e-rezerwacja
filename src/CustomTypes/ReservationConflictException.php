<?php

namespace App\CustomTypes;

use Exception;

class ReservationConflictException extends Exception
{
    public function __construct($param)
    {
        parent::__construct($param, 0, null);
    }
}
