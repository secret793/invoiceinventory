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
            $where[] = '(t.device_serial ILIKE ? OR d.device_id ILIKE ?)';
            array_push($params, $s, $s);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';

        $select = implode(', ', [
            't.*',
            'd.device_id as device_identifier',
            'd.device_type',
            'd.status as device_current_status',
            'dp_to.name   as to_location_name',
            'dp_from.name as from_location_name',
            'ap_to.name   as to_ap_name',
            'ap_from.name as from_ap_name',
        ]);

        $joins = implode(' ', [
            't',
            'LEFT JOIN devices d             ON t.device_id = d.id',
            'LEFT JOIN distribution_points dp_to   ON t.to_distribution_point_id = dp_to.id',
            'LEFT JOIN distribution_points dp_from  ON t.original_distribution_point_id = dp_from.id',
            'LEFT JOIN allocation_points   ap_to   ON t.to_allocation_point_id = ap_to.id',
            'LEFT JOIN allocation_points   ap_from  ON t.original_allocation_point_id = ap_from.id',
        ]);

        return static::paginate($page, $perPage, $whereStr, $params, $select, $joins, 't.created_at DESC');
    }

    public static function bulkCancel(array $ids, string $reason, int $userId): int
    {
        $places = implode(',', array_fill(0, count($ids), '?'));

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
            } elseif ($t['transfer_type'] === 'DISTRIBUTION' && $t['original_distribution_point_id']) {
                Database::execute(
                    'UPDATE devices SET distribution_point_id = ?, updated_at = NOW() WHERE id = ?',
                    [$t['original_distribution_point_id'], $t['device_id']]
                );
            }
        }

        Database::execute(
            "DELETE FROM transfers WHERE id IN ($places) AND transfer_status = 'PENDING'",
            $ids
        );

        return count($transfers);
    }
}
