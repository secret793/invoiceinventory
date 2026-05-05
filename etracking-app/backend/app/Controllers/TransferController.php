<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Transfer;
use App\Models\Device;

class TransferController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $filters = [
            'transfer_status' => $req->query('transfer_status'),
            'transfer_type'   => $req->query('transfer_type'),
            'search'          => $req->query('search'),
        ];
        $result = Transfer::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function store(Request $req): void
    {
        $data = $req->json();
        $user = $req->user();

        if (empty($data['device_id'])) Response::error('device_id is required');

        $device = Device::findOrFail((int) $data['device_id']);

        // Store original allocation/distribution for potential rollback
        $data['original_allocation_point_id'] = $device['allocation_point_id'];
        $data['original_status']              = $device['status'];
        $data['device_serial']                = $device['serial_number'] ?? $device['device_id'];
        $data['transfer_status']              = 'PENDING';
        $data['quantity']                     = $data['quantity'] ?? 1;

        // Update device location
        if (($data['transfer_type'] ?? '') === 'ALLOCATION' && !empty($data['to_allocation_point_id'])) {
            Device::update((int) $data['device_id'], [
                'allocation_point_id' => $data['to_allocation_point_id'],
            ]);
        } elseif (!empty($data['to_distribution_point_id'])) {
            Device::update((int) $data['device_id'], [
                'distribution_point_id' => $data['to_distribution_point_id'],
            ]);
        }

        $row = Transfer::create($data);
        Response::success($row, 'Transfer created', 201);
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        Transfer::findOrFail($id);
        Transfer::delete($id);
        Response::success(null, 'Transfer deleted');
    }

    public function bulkCancel(Request $req): void
    {
        $data   = $req->json();
        $ids    = $data['ids']    ?? [];
        $reason = $data['cancellation_reason'] ?? '';
        $user   = $req->user();

        if (empty($ids)) Response::error('ids are required');
        if (!$reason)    Response::error('cancellation_reason is required');

        $count = Transfer::bulkCancel($ids, $reason, (int) $user['id']);
        Response::success(['cancelled' => $count], "$count transfer(s) cancelled");
    }
}
