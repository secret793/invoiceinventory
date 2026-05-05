<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\User;

class UserController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $search  = $req->query('search', '');

        $result = User::listWithRoles($page, $perPage, $search);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        $id   = (int) $req->param('id');
        $user = User::find($id);
        if (!$user) Response::notFound('User not found');

        unset($user['password']);
        $user['roles']       = User::getRoles($id);
        $user['permissions'] = User::getAllPermissions($id);
        Response::success($user);
    }

    public function store(Request $req): void
    {
        $data = $req->validated([
            'name'     => 'required',
            'email'    => 'required',
            'password' => 'required',
        ]);

        if (User::emailExists($data['email'])) {
            Response::error('Email address is already in use', 422);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'username' => $req->input('username'),
            'password' => Auth::hashPassword($data['password']),
            'role'     => $req->input('role', 'user'),
        ]);

        // Sync roles via model_has_roles if available
        $this->syncRoles((int) $user['id'], $req->input('roles', []));

        unset($user['password']);
        Response::success($user, 'User created successfully', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        User::findOrFail($id);

        $updates = ['name' => $data['name'] ?? null, 'username' => $data['username'] ?? null, 'role' => $data['role'] ?? null];
        $updates = array_filter($updates, fn($v) => $v !== null);

        if (!empty($data['password'])) {
            $updates['password'] = Auth::hashPassword($data['password']);
        }
        if (!empty($data['email']) && !User::emailExists($data['email'], $id)) {
            $updates['email'] = $data['email'];
        }

        $user = User::update($id, $updates);

        if (!empty($data['roles'])) $this->syncRoles($id, $data['roles']);
        if (!empty($data['permissions'])) $this->syncDirectPermissions($id, $data['permissions']);

        unset($user['password']);
        $user['roles']       = User::getRoles($id);
        $user['permissions'] = User::getAllPermissions($id);
        Response::success($user, 'User updated successfully');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        User::findOrFail($id);
        User::delete($id);
        Response::success(null, 'User deleted');
    }

    private function syncRoles(int $userId, array $roleNames): void
    {
        try {
            Database::execute(
                "DELETE FROM model_has_roles WHERE model_id = ? AND model_type LIKE '%User'",
                [$userId]
            );
            foreach ($roleNames as $roleName) {
                $role = Database::queryOne('SELECT id FROM roles WHERE name = ?', [$roleName]);
                if ($role) {
                    Database::execute(
                        "INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (?, 'App\\Models\\User', ?) ON CONFLICT DO NOTHING",
                        [$role['id'], $userId]
                    );
                }
            }
        } catch (\Throwable) {}
    }

    private function syncDirectPermissions(int $userId, array $permNames): void
    {
        try {
            Database::execute(
                "DELETE FROM model_has_permissions WHERE model_id = ? AND model_type LIKE '%User'",
                [$userId]
            );
            foreach ($permNames as $permName) {
                $perm = Database::queryOne('SELECT id FROM permissions WHERE name = ?', [$permName]);
                if ($perm) {
                    Database::execute(
                        "INSERT INTO model_has_permissions (permission_id, model_type, model_id) VALUES (?, 'App\\Models\\User', ?) ON CONFLICT DO NOTHING",
                        [$perm['id'], $userId]
                    );
                }
            }
        } catch (\Throwable) {}
    }
}
