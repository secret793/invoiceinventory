<?php

namespace App\Models;

use App\Core\Database;

class ConfirmedAffixed extends BaseModel
{
    protected static string $table = 'confirmed_affixeds';

    public static function listPaginated(int $page, int $perPage, array $filters = [], array $permittedApIds = [], array $permittedDestIds = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'ca.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(ca.boe LIKE ? OR ca.vehicle_number LIKE ? OR ca.driver_name LIKE ?)';
            array_push($params, $s, $s, $s);
        }
        if (!empty($filters['allocation_point_id'])) {
            $where[]  = 'ca.allocation_point_id = ?';
            $params[] = $filters['allocation_point_id'];
        }
        if (!empty($permittedApIds)) {
            $places  = implode(',', array_fill(0, count($permittedApIds), '?'));
            $where[] = "ca.allocation_point_id IN ($places)";
            array_push($params, ...$permittedApIds);
        }
        if (!empty($permittedDestIds)) {
            $places  = implode(',', array_fill(0, count($permittedDestIds), '?'));
            $where[] = "ca.destination_id IN ($places)";
            array_push($params, ...$permittedDestIds);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';

        return static::paginate($page, $perPage, $whereStr, $params,
            'ca.*, ap.name as allocation_point_name, r.name as route_name, lr.name as long_route_name',
            'ca LEFT JOIN allocation_points ap ON ca.allocation_point_id = ap.id
             LEFT JOIN routes r ON ca.route_id = r.id
             LEFT JOIN long_routes lr ON ca.long_route_id = lr.id',
            'ca.date DESC'
        );
    }

    public static function reportData(array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(ca.boe LIKE ? OR ca.vehicle_number LIKE ? OR ca.device_id LIKE ?)';
            array_push($params, $s, $s, $s);
        }
        if (!empty($filters['from'])) { $where[] = 'ca.date >= ?'; $params[] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to']))   { $where[] = 'ca.date <= ?'; $params[] = $filters['to']   . ' 23:59:59'; }
        if (!empty($filters['allocation_point_id'])) {
            $where[]  = 'ca.allocation_point_id = ?';
            $params[] = $filters['allocation_point_id'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'ca.status = ?';
            $params[] = $filters['status'];
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::query(
            "SELECT ca.*, ap.name as allocation_point_name, d.device_id as device_identifier
             FROM confirmed_affixeds ca
             LEFT JOIN allocation_points ap ON ca.allocation_point_id = ap.id
             LEFT JOIN devices d ON ca.device_id = d.id
             $whereStr ORDER BY ca.date DESC",
            $params
        );
    }
}
