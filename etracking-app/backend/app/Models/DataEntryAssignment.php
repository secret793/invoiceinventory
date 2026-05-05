<?php

namespace App\Models;

use App\Core\Database;

class DataEntryAssignment extends BaseModel
{
    protected static string $table = 'data_entry_assignments';

    public static function listPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['allocation_point_id'])) {
            $where[]  = 'dea.allocation_point_id = ?';
            $params[] = $filters['allocation_point_id'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'dea.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['show_in_menu'])) {
            $where[] = 'dea.show_in_menu = 1';
        }
        // Restrict by permitted allocation points for non-admin users
        if (!empty($filters['permitted_ap_ids'])) {
            $places  = implode(',', array_fill(0, count($filters['permitted_ap_ids']), '?'));
            $where[] = "dea.allocation_point_id IN ($places)";
            array_push($params, ...$filters['permitted_ap_ids']);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';

        return static::paginate($page, $perPage, $whereStr, $params,
            'dea.*, ap.name as allocation_point_name, d.device_id as device_identifier, d.device_type',
            'dea LEFT JOIN allocation_points ap ON dea.allocation_point_id = ap.id
             LEFT JOIN devices d ON dea.device_id = d.id',
            'dea.created_at DESC'
        );
    }
}
