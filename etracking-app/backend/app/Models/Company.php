<?php

namespace App\Models;

use App\Core\Database;

class Company extends BaseModel
{
    protected static string $table = 'companies';

    public static function all(string $orderBy = 'name', string $dir = 'ASC'): array
    {
        return Database::query("SELECT * FROM companies ORDER BY name");
    }

    public static function active(): array
    {
        return Database::query("SELECT * FROM companies WHERE status = 'Active' ORDER BY name");
    }
}
