<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AuthMiddleware
{
    public function handle(Request $req): void
    {
        $token = $req->bearerToken();
        if (!$token) {
            Response::unauthorized('No token provided. Please log in.');
        }

        $payload = Auth::verifyJWT($token);
        if (!$payload) {
            Response::unauthorized('Invalid or expired token. Please log in again.');
        }

        $user = User::findWithRolesAndPermissions((int) $payload['sub']);
        if (!$user) {
            Response::unauthorized('User not found.');
        }

        $req->setUser($user);
    }
}
