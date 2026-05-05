<?php

namespace App\Services;

use App\Core\Database;
use App\Models\AllocationPoint;

class PermissionService
{
    public static function hasRole(array $user, string $role): bool
    {
        return in_array($role, $user['roles'] ?? []);
    }

    public static function hasAnyRole(array $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (self::hasRole($user, $role)) return true;
        }
        return false;
    }

    public static function isSuperAdmin(array $user): bool
    {
        return self::hasRole($user, 'Super Admin');
    }

    public static function hasPermission(array $user, string $permission): bool
    {
        if (self::isSuperAdmin($user)) return true;
        return in_array($permission, $user['permissions'] ?? []);
    }

    public static function filterAllocationPointIds(array $user): ?array
    {
        if (self::hasAnyRole($user, ['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return null; // No filter — access all
        }
        return AllocationPoint::getPermittedForUser($user);
    }

    public static function filterDestinationIds(array $user): ?array
    {
        if (self::hasAnyRole($user, ['Super Admin', 'Warehouse Manager', 'Finance Officer'])) {
            return null;
        }

        $perms = $user['permissions'] ?? [];
        $ids   = [];
        foreach ($perms as $perm) {
            if (preg_match('/^view_destination_(.+)$/', $perm, $m)) {
                $dest = Database::queryOne(
                    "SELECT id FROM destinations WHERE LOWER(REPLACE(name,' ','_')) = ?",
                    [$m[1]]
                );
                if ($dest) $ids[] = (int) $dest['id'];
            }
        }
        return $ids;
    }

    public static function canManageInventory(array $user): bool
    {
        return self::hasAnyRole($user, ['Super Admin', 'Warehouse Manager']);
    }

    public static function canManageDistribution(array $user): bool
    {
        return self::hasAnyRole($user, ['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }
}
