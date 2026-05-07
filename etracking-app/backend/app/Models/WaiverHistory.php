<?php

namespace App\Models;

use App\Core\Database;

class WaiverHistory extends BaseModel
{
    protected static string $table = 'waiver_history';

    public static function findByRetrieval(int $retrievalId): ?array
    {
        return Database::queryOne(
            'SELECT wh.*, u.name as admin_name
             FROM waiver_history wh
             LEFT JOIN users u ON wh.admin_user_id = u.id
             WHERE wh.device_retrieval_id = ?
             ORDER BY wh.id DESC LIMIT 1',
            [$retrievalId]
        );
    }

    public static function existsForRetrieval(int $retrievalId): bool
    {
        $row = Database::queryOne(
            'SELECT id FROM waiver_history WHERE device_retrieval_id = ? LIMIT 1',
            [$retrievalId]
        );
        return !empty($row);
    }
}
