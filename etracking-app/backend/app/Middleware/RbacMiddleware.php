<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RbacMiddleware
{
    public function __construct(private array $roles) {}

    public function handle(Request $req): void
    {
        $user = $req->user();
        if (!$user) {
            Response::unauthorized();
        }

        foreach ($this->roles as $role) {
            if (in_array($role, $user['roles'] ?? [])) {
                return;
            }
        }

        Response::forbidden('You do not have the required role to perform this action.');
    }
}
