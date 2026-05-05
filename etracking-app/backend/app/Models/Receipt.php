<?php

namespace App\Models;

use App\Core\Database;

class Receipt extends BaseModel
{
    protected static string $table = 'receipts';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['allocation_point_id'])) { $where[] = 'r.allocation_point_id = ?'; $params[] = $filters['allocation_point_id']; }
        if (!empty($filters['from'])) { $where[] = 'r.date >= ?'; $params[] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to']))   { $where[] = 'r.date <= ?'; $params[] = $filters['to']   . ' 23:59:59'; }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(r.receipt_number LIKE ? OR r.sad_number LIKE ? OR r.agent_name LIKE ?)';
            array_push($params, $s, $s, $s);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';
        return static::paginate($page, $perPage, $whereStr, $params,
            'r.*, ap.name as station_name, ro.name as route_name, lr.name as long_route_name',
            'r LEFT JOIN allocation_points ap ON r.allocation_point_id = ap.id
             LEFT JOIN routes ro ON r.route_id = ro.id
             LEFT JOIN long_routes lr ON r.long_route_id = lr.id',
            'r.date DESC'
        );
    }
}
