<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Device;
use App\Models\Store;
use App\Services\NotificationService;
use App\Services\PermissionService;

class DeviceController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));

        $filters = [
            'status'                 => $req->query('status'),
            'search'                 => $req->query('search'),
            'allocation_point_id'    => $req->query('allocation_point_id'),
            'distribution_point_id'  => $req->query('distribution_point_id'),
            'exclude_unconfigured'   => $req->query('exclude_unconfigured', false),
        ];

        $result = Device::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function stats(Request $req): void
    {
        Response::success(Device::statusCounts());
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Device::findOrFail($id);
        Response::success($row);
    }

    public function store(Request $req): void
    {
        $data = $req->validated([
            'device_type'   => 'required',
            'device_id'     => 'required',
            'date_received' => 'required',
        ]);

        $user = $req->user();
        $data['user_id']      = $user['id'];
        $data['status']       = $data['status'] ?? 'UNCONFIGURED';
        $data['is_configured'] = isset($data['is_configured']) ? (int) $data['is_configured'] : 0;
        $data['batch_number'] = $data['batch_number'] ?? ('BATCH-' . date('Ymd'));

        $device = Device::create($data);

        // Auto-create Store mirror
        try {
            Store::create([
                'device_id'     => $device['id'],
                'serial_number' => $data['serial_number'] ?? $data['device_id'],
                'device_type'   => $data['device_type'],
                'batch_number'  => $data['batch_number'],
                'date_received' => $data['date_received'],
                'status'        => $data['status'],
                'sim_number'    => $data['sim_number'] ?? null,
                'sim_operator'  => $data['sim_operator'] ?? null,
                'user_id'       => $user['id'],
            ]);
        } catch (\Throwable) {}

        NotificationService::created('Device', $data['device_id']);
        Response::success($device, 'Device created successfully', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        unset($data['id'], $data['created_at']);

        $device = Device::update($id, $data);

        // Sync store mirror
        Store::syncFromDevice($device);

        NotificationService::updated('Device', (string) $id);
        Response::success($device, 'Device updated successfully');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        Device::findOrFail($id);
        Device::delete($id);
        NotificationService::deleted('Device', (string) $id);
        Response::success(null, 'Device deleted successfully');
    }

    public function bulkStatus(Request $req): void
    {
        $data   = $req->json();
        $ids    = $data['ids']    ?? [];
        $status = $data['status'] ?? '';

        if (empty($ids) || !$status) {
            Response::error('ids and status are required');
        }

        $count = Device::bulkUpdateStatus($ids, $status);
        Response::success(['updated' => $count], "Status updated for $count device(s)");
    }

    public function syncICloud(Request $req): void
    {
        // Placeholder — iCloud sync would call external API here
        Response::success([], 'iCloud sync initiated (not implemented in standalone mode)');
    }
}
