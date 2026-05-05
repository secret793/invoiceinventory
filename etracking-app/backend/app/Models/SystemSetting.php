<?php

namespace App\Models;

use App\Core\Database;

class SystemSetting extends BaseModel
{
    protected static string $table = 'system_settings';

    public static function findByKey(string $key): ?array
    {
        return Database::queryOne('SELECT * FROM system_settings WHERE key = ?', [$key]);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::findByKey($key);
        return $row ? $row['value'] : $default;
    }
}
