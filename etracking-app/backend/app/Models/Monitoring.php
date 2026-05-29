<?php

namespace App\Models;

use App\Core\Database;

class Monitoring extends BaseModel
{
    protected static string $table = 'monitorings';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['overstay_min'])) { $where[] = 'm.overstay_days >= ?'; $params[] = $filters['overstay_min']; }
        if (!empty($filters['overstay_max'])) { $where[] = 'm.overstay_days <= ?'; $params[] = $filters['overstay_max']; }
        if (!empty($filters['retrieval_status'])) { $where[] = 'm.retrieval_status = ?'; $params[] = $filters['retrieval_status']; }
        if (!empty($filters['overdue'])) { $where[] = 'm.overstay_days > 0'; }
        if (!empty($filters['pending'])) { $where[] = '(m.manifest_date IS NULL OR m.manifest_date = \'\')'; }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(m.boe ILIKE ? OR m.vehicle_number ILIKE ? OR d.device_id ILIKE ?)';
            array_push($params, $s, $s, $s);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';

        return static::paginate($page, $perPage, $whereStr, $params,
            'm.*, m.date as dispatch_date, d.device_id as device_identifier,
             ap.name as allocation_point_name, r.name as route_name,
             lr.name as long_route_name, dest.name as destination_name',
            'm LEFT JOIN devices d ON m.device_id = d.id
             LEFT JOIN allocation_points ap ON m.allocation_point_id = ap.id
             LEFT JOIN routes r ON m.route_id = r.id
             LEFT JOIN long_routes lr ON m.long_route_id = lr.id
             LEFT JOIN destinations dest ON m.destination_id = dest.id',
            'm.date DESC'
        );
    }
}
