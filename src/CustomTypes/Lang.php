<?php

namespace App\CustomTypes;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\InputBag;

abstract class Lang extends CustomType
{
    const PL = 'pl';
    const EN = 'en';

    protected const VALUES = [
        self::PL => 'Polski',
        self::EN => 'Angielski',
    ];

    public static function fromCookie(InputBag $cookies): string
    {
        return static::valid($cookies->get('LANG', static::PL));
    }
    
    public static function createCookie(string $lang): Cookie
    {
        return new Cookie('LANG', static::valid($lang), 'now +1 year');
    }
}