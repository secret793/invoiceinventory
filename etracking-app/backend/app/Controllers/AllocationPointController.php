<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\AllocationPoint;
use App\Models\Permission;
use App\Models\Transfer;
use App\Models\Device;

class AllocationPointController
{
    public function index(Request $req): void
    {
        Response::success(AllocationPoint::allWithCounts());
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = AllocationPoint::findWithCounts($id);
        if (!$row) Response::notFound('Allocation point not found');
        Response::success($row);
    }

    public function devices(Request $req): void
    {
        $id      = (int) $req->param('id');
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = (int) $req->query('per_page', 25);
        $status  = $req->query('status');

        AllocationPoint::findOrFail($id);

        $where  = 'd.allocation_point_id = ?';
        $params = [$id];
        if ($status) {
            $where   .= ' AND d.status = ?';
            $params[] = $status;
        }

        $result = Device::paginate($page, $perPage, $where, $params,
            'd.*', 'd', 'd.date_received DESC');
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function statusCounts(Request $req): void
    {
        $id   = (int) $req->param('id');
        $rows = Database::query(
            "SELECT status, COUNT(*) as cnt FROM devices WHERE allocation_point_id = ? GROUP BY status",
            [$id]
        );
        $counts = [];
        foreach ($rows as $r) $counts[$r['status']] = (int) $r['cnt'];
        Response::success($counts);
    }

    public function sendToAP(Request $req): void
    {
        $apId       = (int) $req->param('id');
        $data       = $req->json();
        $ids        = $data['device_ids']          ?? [];
        $targetApId = $data['allocation_point_id'] ?? null;

        if (empty($ids))  Response::error('device_ids are required');
        if (!$targetApId) Response::error('allocation_point_id is required');

        $processed = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device || $device['status'] === 'RECEIVED') continue;
            if ($device['allocation_point_id'] != $apId) continue;

            Transfer::create([
                'device_id'                   => $deviceId,
                'device_serial'               => $device['serial_number'] ?? $device['device_id'],
                'transfer_type'               => 'ALLOCATION',
                'transfer_status'             => 'COMPLETED',
                'from_allocation_point_id'    => $apId,
                'to_allocation_point_id'      => $targetApId,
                'original_allocation_point_id'=> $device['allocation_point_id'],
                'original_status'             => $device['status'],
                'quantity'                    => 1,
            ]);

            Database::execute(
                'UPDATE devices SET allocation_point_id = ?, status = ?, updated_at = NOW() WHERE id = ?',
                [$targetApId, 'RECEIVED', $deviceId]
            );
            $processed++;
        }

        Response::success(['sent' => $processed], "$processed device(s) sent to allocation point");
    }

    public function returnToInventory(Request $req): void
    {
        $apId = (int) $req->param('id');
        $data = $req->json();
        $ids  = $data['device_ids'] ?? [];

        if (empty($ids)) Response::error('device_ids are required');

        $processed = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device) continue;
            if ($device['allocation_point_id'] != $apId) continue;

            Transfer::create([
                'device_id'                   => $deviceId,
                'device_serial'               => $device['serial_number'] ?? $device['device_id'],
                'transfer_type'               => 'ALLOCATION',
                'transfer_status'             => 'COMPLETED',
                'from_allocation_point_id'    => $apId,
                'original_allocation_point_id'=> $device['allocation_point_id'],
                'original_status'             => $device['status'],
                'quantity'                    => 1,
            ]);

            Database::execute(
                'UPDATE devices SET allocation_point_id = NULL, updated_at = NOW() WHERE id = ?',
                [$deviceId]
            );
            $processed++;
        }

        Response::success(['returned' => $processed], "$processed device(s) returned to inventory");
    }

    public function changeStatus(Request $req): void
    {
        $apId   = (int) $req->param('id');
        $data   = $req->json();
        $ids    = $data['device_ids'] ?? [];
        $status = $data['status']     ?? null;

        if (empty($ids)) Response::error('device_ids are required');
        if (!$status)    Response::error('status is required');

        $places  = implode(',', array_fill(0, count($ids), '?'));
        $updated = Database::execute(
            "UPDATE devices SET status = ?, updated_at = NOW() WHERE id IN ($places) AND allocation_point_id = ? AND status != 'RECEIVED'",
            [$status, ...$ids, $apId]
        );

        Response::success(['updated' => $updated], "$updated device(s) status changed");
    }

    public function store(Request $req): void
    {
        $data = $req->validated(['name' => 'required', 'location' => 'required']);
        $row  = AllocationPoint::create($data);

        $slug = AllocationPoint::slugify($data['name']);
        Permission::createForAllocationPoint($slug);

        Response::success($row, 'Allocation point created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        AllocationPoint::findOrFail($id);
        $row = AllocationPoint::update($id, $data);
        Response::success($row, 'Allocation point updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        AllocationPoint::findOrFail($id);
        AllocationPoint::delete($id);
        Response::success(null, 'Allocation point deleted');
    }
}
