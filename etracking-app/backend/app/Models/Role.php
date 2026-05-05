<?php

namespace App\Models;

use App\Core\Database;

class Role extends BaseModel
{
    protected static string $table = 'roles';

    public static function allWithPermissions(): array
    {
        $roles = static::all('name', 'ASC');
        foreach ($roles as &$role) {
            $role['permissions'] = array_column(Database::query(
                'SELECT p.name FROM permissions p
                 JOIN role_has_permissions rhp ON p.id = rhp.permission_id
                 WHERE rhp.role_id = ?',
                [$role['id']]
            ), 'name');
        }
        return $roles;
    }

    public static function syncPermissions(int $roleId, array $permissionNames): void
    {
        Database::execute('DELETE FROM role_has_permissions WHERE role_id = ?', [$roleId]);
        foreach ($permissionNames as $name) {
            $perm = Database::queryOne('SELECT id FROM permissions WHERE name = ?', [$name]);
            if ($perm) {
                Database::execute(
                    'INSERT INTO role_has_permissions (role_id, permission_id) VALUES (?, ?) ON CONFLICT DO NOTHING',
                    [$roleId, $perm['id']]
                );
            }
        }
    }
}
