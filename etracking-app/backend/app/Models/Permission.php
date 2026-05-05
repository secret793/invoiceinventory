<?php

namespace App\Models;

use App\Core\Database;

class Permission extends BaseModel
{
    protected static string $table = 'permissions';

    public static function allGrouped(): array
    {
        $all = static::all('name', 'ASC');
        $grouped = ['allocation_points' => [], 'destinations' => [], 'other' => []];

        foreach ($all as $p) {
            if (str_contains($p['name'], 'allocationpoint')) {
                $grouped['allocation_points'][] = $p;
            } elseif (str_contains($p['name'], 'destination')) {
                $grouped['destinations'][] = $p;
            } else {
                $grouped['other'][] = $p;
            }
        }
        return $grouped;
    }

    public static function createForAllocationPoint(string $slug): void
    {
        foreach (['view_allocationpoint_', 'edit_allocationpoint_', 'view_data_entry_'] as $prefix) {
            $name = $prefix . $slug;
            if (!Database::queryOne('SELECT id FROM permissions WHERE name = ?', [$name])) {
                Database::execute(
                    "INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES (?, 'web', NOW(), NOW())",
                    [$name]
                );
            }
        }
    }

    public static function createForDestination(string $slug): void
    {
        foreach (['view_destination_', 'manage_devices_destination_'] as $prefix) {
            $name = $prefix . $slug;
            if (!Database::queryOne('SELECT id FROM permissions WHERE name = ?', [$name])) {
                Database::execute(
                    "INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES (?, 'web', NOW(), NOW())",
                    [$name]
                );
            }
        }
    }
}
