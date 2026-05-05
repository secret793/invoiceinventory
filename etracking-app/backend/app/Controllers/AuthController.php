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
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            Response::error('Email and password are required', 422);
        }

        $user = User::findByEmail($email);

        if (!$user || !Auth::verifyPassword($password, $user['password'])) {
            Response::unauthorized('Invalid email or password. Please try again.');
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
