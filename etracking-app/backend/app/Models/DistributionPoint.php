<?php

namespace App\Models;

use App\Core\Database;

class DistributionPoint extends BaseModel
{
    protected static string $table = 'distribution_points';

    public static function allWithCounts(): array
    {
        return Database::query(
            "SELECT dp.*,
                    COUNT(CASE WHEN d.status = 'RECEIVED' THEN 1 END) as received_count,
                    COUNT(CASE WHEN d.status != 'RECEIVED' AND d.status IS NOT NULL THEN 1 END) as other_count
             FROM distribution_points dp
             LEFT JOIN devices d ON d.distribution_point_id = dp.id
             GROUP BY dp.id
             ORDER BY dp.name"
        );
    }

    public static function findWithDevices(int $id, int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $where  = ['d.distribution_point_id = ?'];
        $params = [$id];

        if (!empty($filters['status'])) {
            $where[]  = 'd.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[]  = '(d.device_id LIKE ? OR d.serial_number LIKE ?)';
            array_push($params, $s, $s);
        }

        return Device::paginate($page, $perPage, implode(' AND ', $where), $params,
            'd.*', 'd', 'd.date_received DESC');
    }
}
