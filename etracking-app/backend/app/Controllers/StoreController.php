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
        $perPage = min(1000, max(1, (int) $req->query('per_page', 25)));
        $filters = ['status' => $req->query('status'), 'search' => $req->query('search')];

        $result = Store::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function stats(Request $req): void
    {
        Response::success(Store::statusCounts());
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
        $existing = Store::findOrFail($id);

        // Only allow updating these fields from the stores page
        $allowed = array_intersect_key($data, array_flip(['status', 'sim_number', 'sim_operator', 'batch_number']));
        $row = Store::update($id, $allowed);

        // Sync back to devices table if linked
        if (!empty($existing['device_id'])) {
            $deviceUpdates = array_intersect_key($allowed, array_flip(['status', 'sim_number', 'sim_operator']));
            if ($deviceUpdates) {
                $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($deviceUpdates)));
                Database::execute(
                    "UPDATE devices SET $sets, updated_at = NOW() WHERE id = ?",
                    [...array_values($deviceUpdates), $existing['device_id']]
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

        // Also delete linked device
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

        // Sync status to linked devices
        foreach ($ids as $storeId) {
            $store = Store::find((int) $storeId);
            if ($store && !empty($store['device_id'])) {
                Database::execute(
                    'UPDATE devices SET status = ?, updated_at = NOW() WHERE id = ?',
                    [$status, $store['device_id']]
                );
            }
        }

        Response::success(['updated' => $count], "$count item(s) updated to $status");
    }

    public function bulkDelete(Request $req): void
    {
        $data = $req->json();
        $ids  = $data['ids'] ?? [];
        if (empty($ids)) Response::error('ids are required');

        $count = 0;
        foreach ($ids as $id) {
            $row = Store::find((int) $id);
            if (!$row) continue;
            Store::delete((int) $id);
            if (!empty($row['device_id'])) {
                Device::delete((int) $row['device_id']);
            }
            $count++;
        }

        Response::success(['deleted' => $count], "$count item(s) deleted");
    }
}
