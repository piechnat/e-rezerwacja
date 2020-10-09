<?php

namespace App\Service;

use Exception;

class NotAllowedException extends Exception
{
    public function __construct($param)
    {
        parent::__construct($param, $param, null);
    }
}
