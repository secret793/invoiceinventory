<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Role;

class RoleController
{
    public function index(Request $req): void  { Response::success(Role::allWithPermissions()); }
    public function show(Request $req): void   { Response::success(Role::findOrFail((int) $req->param('id'))); }
    public function store(Request $req): void  {
        $data = $req->validated(['name' => 'required']);
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $row  = Role::create($data);
        if (!empty($req->input('permissions'))) {
            Role::syncPermissions((int) $row['id'], $req->input('permissions'));
        }
        Response::success($row, 'Role created', 201);
    }
    public function update(Request $req): void {
        $id = (int) $req->param('id'); Role::findOrFail($id);
        $row = Role::update($id, $req->json());
        if (!empty($req->input('permissions'))) {
            Role::syncPermissions($id, $req->input('permissions'));
        }
        Response::success($row, 'Role updated');
    }
    public function destroy(Request $req): void {
        $id = (int) $req->param('id'); Role::findOrFail($id); Role::delete($id);
        Response::success(null, 'Role deleted');
    }
}
