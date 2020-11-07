<?php

namespace App\CustomTypes;

abstract class UserLevel extends CustomType
{
    const DISABLED = 'ROLE_DISABLED';
    const USER = 'ROLE_USER';
    const SUPER_USER = 'ROLE_SUPER_USER';
    const ADMIN = 'ROLE_ADMIN';
    const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    
    protected const VALUES = [
        self::DISABLED => 'Wyłączone',
        self::USER => 'Student',
        self::SUPER_USER => 'Pracownik',
        self::ADMIN => 'Zarządca',
        self::SUPER_ADMIN => 'Administrator',
    ];
}