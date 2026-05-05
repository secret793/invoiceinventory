<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Store;
use App\Models\Device;
use App\Core\Database;

class StoreController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $filters = ['status' => $req->query('status'), 'search' => $req->query('search')];

        $result = Store::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        Response::success(Store::findOrFail((int) $req->param('id')));
    }

    public function store(Request $req): void
    {
        $data = $req->json();
        if (empty($data['serial_number'])) Response::error('serial_number is required');
        $row = Store::create($data);
        Response::success($row, 'Stock item created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        Store::findOrFail($id);
        $row = Store::update($id, $data);

        // Sync back to devices table if linked
        if (!empty($row['device_id'])) {
            $updates = array_intersect_key($data, array_flip(['status', 'sim_number', 'sim_operator']));
            if ($updates) {
                $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($updates)));
                Database::execute(
                    "UPDATE devices SET $sets, updated_at = NOW() WHERE id = ?",
                    [...array_values($updates), $row['device_id']]
                );
            }
        }

        Response::success($row, 'Stock item updated');
    }

    public function destroy(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Store::findOrFail($id);
        Store::delete($id);

        // Delete linked device
        if (!empty($row['device_id'])) {
            Device::delete((int) $row['device_id']);
        }

        Response::success(null, 'Stock item deleted');
    }

    public function bulkStatus(Request $req): void
    {
        $data   = $req->json();
        $ids    = $data['ids']    ?? [];
        $status = $data['status'] ?? '';
        if (empty($ids) || !$status) Response::error('ids and status are required');
        $count = Store::bulkUpdateStatus($ids, $status);
        Response::success(['updated' => $count]);
    }
}
