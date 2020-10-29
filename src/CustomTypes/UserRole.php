<?php

namespace App\CustomTypes;

abstract class UserRole extends CustomType
{
    const USER = 'ROLE_USER';
    const ADMIN = 'ROLE_ADMIN';
    const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    
    protected const VALUES = [
        self::USER => 'Użytkownik',
        self::ADMIN => 'Administrator',
        self::SUPER_ADMIN => 'Super administrator',
    ];
}