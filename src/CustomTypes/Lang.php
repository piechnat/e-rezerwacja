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
        return static::valid($cookies->get('lang', static::PL));
    }
    
    public static function createCookie(string $lang): Cookie
    {
        return new Cookie('lang', static::valid($lang), 'now +1 year');
    }
}