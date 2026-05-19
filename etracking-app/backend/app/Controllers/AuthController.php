<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\NotificationService;

class AuthController
{
    public function login(Request $req): void
    {
        $data     = $req->json();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (!$username || !$password) {
            Response::error('Username and password are required', 422);
        }

        $user = User::findByUsername($username);

        if (!$user || !Auth::verifyPassword($password, $user['password'])) {
            Response::unauthorized('Invalid username or password. Please try again.');
        }

        $roles       = User::getRoles((int) $user['id']);
        $permissions = User::getAllPermissions((int) $user['id']);

        $token = Auth::generateJWT([
            'sub'         => $user['id'],
            'email'       => $user['email'],
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);

        Response::success([
            'token' => $token,
            'user'  => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'username'    => $user['username'] ?? null,
                'roles'       => $roles,
                'permissions' => $permissions,
            ],
        ], 'Login successful');
    }

    public function logout(Request $req): void
    {
        Response::success(null, 'Logged out successfully');
    }

    public function me(Request $req): void
    {
        $user = $req->user();
        Response::success([
            'id'          => $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'username'    => $user['username'] ?? null,
            'roles'       => $user['roles']       ?? [],
            'permissions' => $user['permissions'] ?? [],
        ]);
    }
}
