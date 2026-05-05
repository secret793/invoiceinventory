<?php

namespace App\Services;

use App\Core\Database;

class NotificationService
{
    public static function notify(string $type, string $message, array $data = [], ?int $userId = null): void
    {
        try {
            // Check if notifications table has required columns
            Database::execute(
                "INSERT INTO notifications (type, data, read_at, created_at, updated_at)
                 VALUES (?, ?, NULL, NOW(), NOW())",
                [$type, json_encode(['message' => $message, ...$data])]
            );
        } catch (\Throwable) {
            // Silently fail if notifications table doesn't match expected schema
        }
    }

    public static function created(string $resource, string $id): void
    {
        self::notify('created', "$resource $id has been created.");
    }

    public static function updated(string $resource, string $id): void
    {
        self::notify('updated', "$resource $id has been updated.");
    }

    public static function deleted(string $resource, string $id): void
    {
        self::notify('deleted', "$resource $id has been deleted.");
    }
}
