<?php

namespace App\Models;

use App\Core\Database;

class AllocationPoint extends BaseModel
{
    protected static string $table = 'allocation_points';

    public static function allWithCounts(): array
    {
        return Database::query(
            "SELECT ap.*,
                    COUNT(CASE WHEN d.status = 'RECEIVED' THEN 1 END) as received_count,
                    COUNT(CASE WHEN d.status != 'RECEIVED' AND d.status IS NOT NULL THEN 1 END) as other_count
             FROM allocation_points ap
             LEFT JOIN devices d ON d.allocation_point_id = ap.id
             GROUP BY ap.id
             ORDER BY ap.name"
        );
    }

    public static function findWithCounts(int $id): ?array
    {
        return Database::queryOne(
            "SELECT ap.*,
                    COUNT(CASE WHEN d.status = 'RECEIVED' THEN 1 END) as received_count,
                    COUNT(CASE WHEN d.status != 'RECEIVED' THEN 1 END) as other_count
             FROM allocation_points ap
             LEFT JOIN devices d ON d.allocation_point_id = ap.id
             WHERE ap.id = ?
             GROUP BY ap.id",
            [$id]
        );
    }

    public static function slugify(string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($name)));
    }

    public static function getPermittedForUser(array $user): array
    {
        $perms = $user['permissions'] ?? [];
        $ids   = [];
        foreach ($perms as $perm) {
            if (preg_match('/^view_allocationpoint_(.+)$/', $perm, $m)) {
                $ap = Database::queryOne(
                    "SELECT id FROM allocation_points WHERE LOWER(REPLACE(name,' ','_')) = ?",
                    [$m[1]]
                );
                if ($ap) $ids[] = (int) $ap['id'];
            }
        }
        return $ids;
    }
}
