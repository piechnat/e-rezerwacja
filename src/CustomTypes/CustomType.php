<?php

namespace App\CustomTypes;

use InvalidArgumentException;

abstract class CustomType
{
    protected const VALUES = [];

    public static function getValues(): array
    {
        return static::VALUES;
    }

    public static function getTypes(): array
    {
        return array_keys(static::VALUES);
    }

    public static function valid(string $type): string
    {
        if (array_key_exists($type, static::VALUES)) {
            return $type;
        }

        throw new InvalidArgumentException('There is no such type in '.static::class);
    }

    public static function getValue(string $type): string
    {
        return static::VALUES[static::valid($type)];
    }

    public static function getIndex(string $type): string
    {
        return array_search(static::valid($type), array_keys(static::VALUES));
    }
}
