<?php

namespace App\Models;

use App\Core\Database;

class Destination extends BaseModel
{
    protected static string $table = 'destinations';

    public static function allOrdered(): array
    {
        return Database::query(
            'SELECT d.*, r.name as regime_name FROM destinations d
             LEFT JOIN regimes r ON d.regime_id = r.id
             ORDER BY d.name'
        );
    }

    public static function slugify(string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($name)));
    }
}
