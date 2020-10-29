<?php

namespace App\CustomTypes;

use Exception;

class NotAllowedException extends Exception
{
    public function __construct($param)
    {
        parent::__construct($param, 0, null);
    }
}
