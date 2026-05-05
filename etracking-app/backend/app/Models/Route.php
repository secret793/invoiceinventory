<?php

namespace App\Models;

class Route extends BaseModel
{
    protected static string $table = 'routes';

    public static function allOrdered(): array
    {
        return static::all('name', 'ASC');
    }
}
