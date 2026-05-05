<?php

namespace App\Models;

use App\Core\Database;

class DeviceRetrieval extends BaseModel
{
    protected static string $table = 'device_retrievals';

    public static function listPaginated(int $page, int $perPage, array $filters = [], array $permittedDestIds = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'dr.status = ?'; $params[] = $filters['status'];
        }
        if (!empty($filters['retrieval_status'])) {
            $where[] = 'dr.retrieval_status = ?'; $params[] = $filters['retrieval_status'];
        }
        if (!empty($filters['payment_status'])) {
            $where[] = 'dr.payment_status = ?'; $params[] = $filters['payment_status'];
        }
        if (!empty($filters['allocation_point_id'])) {
            $aps = explode(',', $filters['allocation_point_id']);
            $places = implode(',', array_fill(0, count($aps), '?'));
            $where[] = "dr.allocation_point_id IN ($places)";
            array_push($params, ...$aps);
        }
        if (!empty($filters['destination_id'])) {
            $dests = explode(',', $filters['destination_id']);
            $places = implode(',', array_fill(0, count($dests), '?'));
            $where[] = "dr.destination_id IN ($places)";
            array_push($params, ...$dests);
        }
        if (!empty($filters['route_type'])) {
            if ($filters['route_type'] === 'long')  $where[] = 'dr.long_route_id IS NOT NULL';
            else                                    $where[] = 'dr.route_id IS NOT NULL AND dr.long_route_id IS NULL';
        }
        if (isset($filters['overdue']) && $filters['overdue']) {
            $where[] = 'dr.overstay_days > 0';
        }
        if (!empty($filters['overstay_min'])) {
            $where[] = 'dr.overstay_days >= ?'; $params[] = $filters['overstay_min'];
        }
        if (!empty($filters['overstay_max'])) {
            $where[] = 'dr.overstay_days <= ?'; $params[] = $filters['overstay_max'];
        }
        if (!empty($filters['from'])) { $where[] = 'dr.date >= ?'; $params[] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to']))   { $where[] = 'dr.date <= ?'; $params[] = $filters['to']   . ' 23:59:59'; }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(dr.boe LIKE ? OR dr.vehicle_number LIKE ? OR d.device_id LIKE ? OR dr.t1_validation_ref LIKE ?)';
            array_push($params, $s, $s, $s, $s);
        }
        if (!empty($permittedDestIds)) {
            $places = implode(',', array_fill(0, count($permittedDestIds), '?'));
            $where[] = "dr.destination_id IN ($places)";
            array_push($params, ...$permittedDestIds);
        }

        $whereStr = $where ? implode(' AND ', $where) : '';

        return static::paginate($page, $perPage, $whereStr, $params,
            'dr.*, d.device_id as device_identifier, ap.name as allocation_point_name,
             r.name as route_name, lr.name as long_route_name, dest.name as destination_name',
            'dr LEFT JOIN devices d ON dr.device_id = d.id
             LEFT JOIN allocation_points ap ON dr.allocation_point_id = ap.id
             LEFT JOIN routes r ON dr.route_id = r.id
             LEFT JOIN long_routes lr ON dr.long_route_id = lr.id
             LEFT JOIN destinations dest ON dr.destination_id = dest.id',
            'dr.date DESC'
        );
    }

    public static function reportData(array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[] = '(dr.boe LIKE ? OR dr.vehicle_number LIKE ? OR d.device_id LIKE ?)';
            array_push($params, $s, $s, $s);
        }
        if (!empty($filters['from']))             { $where[] = 'dr.date >= ?'; $params[] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to']))               { $where[] = 'dr.date <= ?'; $params[] = $filters['to']   . ' 23:59:59'; }
        if (!empty($filters['retrieval_status'])) { $where[] = 'dr.retrieval_status = ?'; $params[] = $filters['retrieval_status']; }
        if (!empty($filters['allocation_point_id'])) { $where[] = 'dr.allocation_point_id = ?'; $params[] = $filters['allocation_point_id']; }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::query(
            "SELECT dr.*, d.device_id as device_identifier, ap.name as allocation_point_name
             FROM device_retrievals dr
             LEFT JOIN devices d ON dr.device_id = d.id
             LEFT JOIN allocation_points ap ON dr.allocation_point_id = ap.id
             $whereStr ORDER BY dr.date DESC",
            $params
        );
    }
}
