<?php

namespace App\Models;

class LongRoute extends BaseModel
{
    protected static string $table = 'long_routes';

    public static function allOrdered(): array
    {
        return static::all('name', 'ASC');
    }
}
