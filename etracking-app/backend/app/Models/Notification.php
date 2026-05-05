<?php

namespace App\Models;

use App\Core\Database;

class Notification extends BaseModel
{
    protected static string $table = 'notifications';

    public static function listPaginated(int $page, int $perPage, ?string $filter = null): array
    {
        $where  = [];
        $params = [];

        if ($filter === 'unread') $where[] = 'read_at IS NULL';
        if ($filter === 'read')   $where[] = 'read_at IS NOT NULL';

        $whereStr = $where ? implode(' AND ', $where) : '';
        return static::paginate($page, $perPage, $whereStr, $params, '*', '', 'created_at DESC');
    }

    public static function unreadCount(): int
    {
        return static::count('read_at IS NULL');
    }

    public static function markRead(array $ids): void
    {
        $places = implode(',', array_fill(0, count($ids), '?'));
        Database::execute(
            "UPDATE notifications SET read_at = NOW() WHERE id IN ($places)",
            $ids
        );
    }

    public static function markUnread(array $ids): void
    {
        $places = implode(',', array_fill(0, count($ids), '?'));
        Database::execute(
            "UPDATE notifications SET read_at = NULL WHERE id IN ($places)",
            $ids
        );
    }
}
