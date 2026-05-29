<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Role;

class RoleController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(1000, max(1, (int) $req->query('per_page', 25)));
        $result  = Role::paginate($page, $perPage, '', [], 'roles.*', '', 'name ASC');

        foreach ($result['data'] as &$role) {
            $role['permissions'] = array_column(\App\Core\Database::query(
                'SELECT p.name FROM permissions p
                 JOIN role_has_permissions rhp ON p.id = rhp.permission_id
                 WHERE rhp.role_id = ?',
                [$role['id']]
            ), 'name');
        }

        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }
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
