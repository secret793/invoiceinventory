<?php

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return Database::queryOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function findWithRolesAndPermissions(int $id): ?array
    {
        $user = static::find($id);
        if (!$user) return null;

        // Load roles via pivot table model_has_roles or roles column
        $user['roles']       = static::getRoles($id);
        $user['permissions'] = static::getAllPermissions($id);
        return $user;
    }

    public static function getRoles(int $userId): array
    {
        // Try model_has_roles (Spatie-style) then fallback to role column
        try {
            $rows = Database::query(
                'SELECT r.name FROM roles r
                 JOIN model_has_roles mhr ON r.id = mhr.role_id
                 WHERE mhr.model_id = ? AND mhr.model_type LIKE "%User"',
                [$userId]
            );
            if ($rows) return array_column($rows, 'name');
        } catch (\Throwable) {}

        // Fallback: single role column
        $user = Database::queryOne('SELECT role FROM users WHERE id = ?', [$userId]);
        return $user && $user['role'] ? [$user['role']] : [];
    }

    public static function getAllPermissions(int $userId): array
    {
        try {
            // Direct user permissions
            $direct = Database::query(
                'SELECT p.name FROM permissions p
                 JOIN model_has_permissions mhp ON p.id = mhp.permission_id
                 WHERE mhp.model_id = ? AND mhp.model_type LIKE "%User"',
                [$userId]
            );
            // Via roles
            $viaRoles = Database::query(
                'SELECT DISTINCT p.name FROM permissions p
                 JOIN role_has_permissions rhp ON p.id = rhp.permission_id
                 JOIN roles r ON r.id = rhp.role_id
                 JOIN model_has_roles mhr ON r.id = mhr.role_id
                 WHERE mhr.model_id = ? AND mhr.model_type LIKE "%User"',
                [$userId]
            );
            return array_unique([
                ...array_column($direct, 'name'),
                ...array_column($viaRoles, 'name'),
            ]);
        } catch (\Throwable) {
            return [];
        }
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            return (bool) Database::queryOne('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $excludeId]);
        }
        return (bool) Database::queryOne('SELECT id FROM users WHERE email = ?', [$email]);
    }

    public static function listWithRoles(int $page, int $perPage, string $search = ''): array
    {
        $where  = $search ? "(name LIKE ? OR email LIKE ?)" : '';
        $params = $search ? ["%$search%", "%$search%"] : [];

        $result = static::paginate($page, $perPage, $where, $params,
            'u.id, u.name, u.email, u.username, u.role, u.created_at',
            'u',
            'u.name ASC'
        );

        // Attach roles/perms
        foreach ($result['data'] as &$row) {
            $row['roles']       = static::getRoles((int) $row['id']);
            $row['permissions'] = static::getAllPermissions((int) $row['id']);
        }
        return $result;
    }
}
