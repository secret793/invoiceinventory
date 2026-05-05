<?php

namespace App\Models;

use App\Core\Database;

class Transfer extends BaseModel
{
    protected static string $table = 'transfers';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['transfer_status'])) { $where[] = 't.transfer_status = ?'; $params[] = $filters['transfer_status']; }
        if (!empty($filters['transfer_type']))   { $where[] = 't.transfer_type = ?';   $params[] = $filters['transfer_type'];   }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(t.device_serial LIKE ? OR d.device_id LIKE ?)';
            array_push($params, $s, $s);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';
        return static::paginate($page, $perPage, $whereStr, $params,
            't.*, d.device_id as device_identifier, d.device_type',
            't LEFT JOIN devices d ON t.device_id = d.id',
            't.created_at DESC'
        );
    }

    public static function bulkCancel(array $ids, string $reason, int $userId): int
    {
        $places = implode(',', array_fill(0, count($ids), '?'));

        // Revert device allocation/distribution for each
        $transfers = Database::query(
            "SELECT * FROM transfers WHERE id IN ($places) AND transfer_status = 'PENDING'",
            $ids
        );

        foreach ($transfers as $t) {
            if ($t['transfer_type'] === 'ALLOCATION' && $t['original_allocation_point_id']) {
                Database::execute(
                    'UPDATE devices SET allocation_point_id = ?, updated_at = NOW() WHERE id = ?',
                    [$t['original_allocation_point_id'], $t['device_id']]
                );
            }
        }

        return Database::execute(
            "UPDATE transfers SET transfer_status = 'CANCELLED', cancellation_reason = ?,
             cancelled_at = NOW(), updated_at = NOW() WHERE id IN ($places) AND transfer_status = 'PENDING'",
            [$reason, ...$ids]
        );
    }
}
