<?php

namespace App\Models;

use App\Core\Database;

class Device extends BaseModel
{
    protected static string $table = 'devices';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $statuses = explode(',', $filters['status']);
            $places   = implode(',', array_fill(0, count($statuses), '?'));
            $where[]  = "d.status IN ($places)";
            array_push($params, ...$statuses);
        }
        if (!empty($filters['search'])) {
            $where[]  = '(d.device_id LIKE ? OR d.serial_number LIKE ? OR d.sim_number LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            array_push($params, $s, $s, $s);
        }
        if (!empty($filters['allocation_point_id'])) {
            $where[]  = 'd.allocation_point_id = ?';
            $params[] = $filters['allocation_point_id'];
        }
        if (!empty($filters['distribution_point_id'])) {
            $where[]  = 'd.distribution_point_id = ?';
            $params[] = $filters['distribution_point_id'];
        }
        if (isset($filters['exclude_unconfigured']) && $filters['exclude_unconfigured']) {
            $where[] = "d.status != 'UNCONFIGURED'";
        }

        $whereStr = $where ? implode(' AND ', $where) : '';

        return static::paginate($page, $perPage, $whereStr, $params,
            'd.*, ap.name as allocation_point_name, dp.name as distribution_point_name',
            'd LEFT JOIN allocation_points ap ON d.allocation_point_id = ap.id
             LEFT JOIN distribution_points dp ON d.distribution_point_id = dp.id',
            'd.date_received DESC'
        );
    }

    public static function statusCounts(): array
    {
        $rows = Database::query('SELECT status, COUNT(*) as cnt FROM devices GROUP BY status');
        $counts = [];
        foreach ($rows as $r) $counts[$r['status']] = (int) $r['cnt'];
        return $counts;
    }

    public static function bulkUpdateStatus(array $ids, string $status): int
    {
        $places = implode(',', array_fill(0, count($ids), '?'));
        return Database::execute(
            "UPDATE devices SET status = ?, updated_at = NOW() WHERE id IN ($places)",
            [$status, ...$ids]
        );
    }
}
