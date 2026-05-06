<?php

namespace App\Models;

use App\Core\Database;

class Store extends BaseModel
{
    protected static string $table = 'stores';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 's.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[]  = '(s.serial_number ILIKE ? OR s.batch_number ILIKE ? OR s.device_type ILIKE ?)';
            array_push($params, $s, $s, $s);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';
        return static::paginate(
            $page, $perPage, $whereStr, $params,
            's.*, u.name as added_by_name',
            's LEFT JOIN users u ON s.user_id = u.id',
            's.created_at DESC'
        );
    }

    public static function statusCounts(): array
    {
        $rows = Database::query('SELECT status, COUNT(*) as cnt FROM stores GROUP BY status');
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        return $counts;
    }

    public static function findBySerial(string $serial): ?array
    {
        return Database::queryOne('SELECT * FROM stores WHERE serial_number = ?', [$serial]);
    }

    public static function syncFromDevice(array $device): void
    {
        $existing = Database::queryOne('SELECT id FROM stores WHERE device_id = ?', [$device['id']]);
        if ($existing) {
            Database::execute(
                'UPDATE stores SET status = ?, sim_number = ?, sim_operator = ?, updated_at = NOW() WHERE device_id = ?',
                [$device['status'], $device['sim_number'] ?? null, $device['sim_operator'] ?? null, $device['id']]
            );
        }
    }

    public static function bulkUpdateStatus(array $ids, string $status): int
    {
        $places = implode(',', array_fill(0, count($ids), '?'));
        return Database::execute(
            "UPDATE stores SET status = ?, updated_at = NOW() WHERE id IN ($places)",
            [$status, ...$ids]
        );
    }

    public static function bulkDelete(array $ids): int
    {
        $places = implode(',', array_fill(0, count($ids), '?'));
        return Database::execute("DELETE FROM stores WHERE id IN ($places)", $ids);
    }
}
