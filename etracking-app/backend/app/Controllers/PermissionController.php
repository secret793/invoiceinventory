<?php

namespace App\Controllers;

use App\Core\Database;
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

    public function store(Request $req): void
    {
        $data = $req->validated(['name' => 'required']);
        $name = strtolower(trim($data['name']));

        if (Database::queryOne('SELECT id FROM permissions WHERE name = ?', [$name])) {
            Response::error("Permission '{$name}' already exists", 422);
        }

        $perm = Database::queryOne(
            "INSERT INTO permissions (name, guard_name, created_at, updated_at)
             VALUES (?, 'web', NOW(), NOW()) RETURNING id, name, guard_name, created_at",
            [$name]
        );
        Response::success($perm, 'Permission created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        $name = strtolower(trim($data['name'] ?? ''));

        if (!$name) Response::error('Permission name is required', 422);

        if (!Database::queryOne('SELECT id FROM permissions WHERE id = ?', [$id])) {
            Response::notFound('Permission not found');
        }
        if (Database::queryOne('SELECT id FROM permissions WHERE name = ? AND id != ?', [$name, $id])) {
            Response::error("Permission '{$name}' already exists", 422);
        }

        Database::execute("UPDATE permissions SET name = ?, updated_at = NOW() WHERE id = ?", [$name, $id]);
        Response::success(['id' => $id, 'name' => $name, 'guard_name' => 'web'], 'Permission updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        if (!Database::queryOne('SELECT id FROM permissions WHERE id = ?', [$id])) {
            Response::notFound('Permission not found');
        }
        Database::execute("DELETE FROM role_has_permissions  WHERE permission_id = ?", [$id]);
        Database::execute("DELETE FROM model_has_permissions WHERE permission_id = ?", [$id]);
        Database::execute("DELETE FROM permissions           WHERE id = ?",            [$id]);
        Response::success(null, 'Permission deleted');
    }

    /**
     * POST /api/permissions/auto-create
     * body: { type: "allocation_point" | "destination", slug: "banjul" }
     * Auto-generates the standard permission set for an AP or Destination slug.
     */
    public function autoCreate(Request $req): void
    {
        $type = $req->input('type', '');
        $slug = strtolower(trim($req->input('slug', '')));

        if (!$slug) Response::error('Slug is required', 422);

        if ($type === 'allocation_point') {
            Permission::createForAllocationPoint($slug);
            Response::success(null, "Allocation point permissions created for '{$slug}'", 201);
        } elseif ($type === 'destination') {
            Permission::createForDestination($slug);
            Response::success(null, "Destination permissions created for '{$slug}'", 201);
        } else {
            Response::error("Invalid type. Use 'allocation_point' or 'destination'", 422);
        }
    }
}
