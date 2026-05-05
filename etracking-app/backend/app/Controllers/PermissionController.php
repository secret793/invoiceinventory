<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Permission;

class PermissionController
{
    public function index(Request $req): void
    {
        $grouped = $req->query('grouped', false);
        if ($grouped) {
            Response::success(Permission::allGrouped());
        } else {
            Response::success(Permission::all('name', 'ASC'));
        }
    }
}
