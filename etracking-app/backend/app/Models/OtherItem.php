<?php

namespace App\Models;

use App\Core\Database;

class OtherItem extends BaseModel
{
    protected static string $table = 'other_items';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }

        $whereStr = $where ? implode(' AND ', $where) : '';
        return static::paginate($page, $perPage, $whereStr, $params, '*', '', 'created_at DESC');
    }

    public static function bulkUpdateStatus(array $ids, string $status): int
    {
        $places = implode(',', array_fill(0, count($ids), '?'));
        return Database::execute(
            "UPDATE other_items SET status = ?, updated_at = NOW() WHERE id IN ($places)",
            [$status, ...$ids]
        );
    }
}
