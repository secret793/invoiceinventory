<?php

namespace App\Models;

class Regime extends BaseModel
{
    protected static string $table = 'regimes';

    public static function active(): array
    {
        return \App\Core\Database::query(
            "SELECT * FROM regimes WHERE is_active = 1 OR status = 'active' ORDER BY name"
        );
    }
}
