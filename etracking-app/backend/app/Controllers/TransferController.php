<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
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

        $data['original_allocation_point_id']    = $device['allocation_point_id'];
        $data['original_distribution_point_id']  = $device['distribution_point_id'];
        $data['original_status']                 = $device['status'];
        $data['device_serial']                   = $device['serial_number'] ?? $device['device_id'];
        $data['transfer_status']                 = 'PENDING';
        $data['quantity']                        = $data['quantity'] ?? 1;
        $data['user_id']                         = $user['id'];

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

    public function bulkApprove(Request $req): void
    {
        $data = $req->json();
        $ids  = $data['ids'] ?? [];

        if (empty($ids)) Response::error('ids are required');

        $places   = implode(',', array_fill(0, count($ids), '?'));
        $transfers = Database::query(
            "SELECT * FROM transfers WHERE id IN ($places) AND transfer_status = 'PENDING'",
            $ids
        );

        if (empty($transfers)) Response::error('No pending transfers found for selected IDs');

        $approved = 0;
        foreach ($transfers as $t) {
            // Update device: status=RECEIVED, set destination point
            $deviceUpdate = ['status' => 'RECEIVED'];
            if ($t['transfer_type'] === 'DISTRIBUTION' && !empty($t['to_distribution_point_id'])) {
                $deviceUpdate['distribution_point_id'] = $t['to_distribution_point_id'];
                $deviceUpdate['allocation_point_id']   = null;
            } elseif ($t['transfer_type'] === 'ALLOCATION' && !empty($t['to_allocation_point_id'])) {
                $deviceUpdate['allocation_point_id']   = $t['to_allocation_point_id'];
                $deviceUpdate['distribution_point_id'] = null;
            }
            Database::execute(
                'UPDATE devices SET status = ?, distribution_point_id = ?, allocation_point_id = ?, updated_at = NOW() WHERE id = ?',
                [
                    $deviceUpdate['status'],
                    $deviceUpdate['distribution_point_id'] ?? null,
                    $deviceUpdate['allocation_point_id']   ?? null,
                    $t['device_id'],
                ]
            );

            // Mark completed then delete
            Database::execute(
                "UPDATE transfers SET transfer_status = 'COMPLETED', updated_at = NOW() WHERE id = ?",
                [$t['id']]
            );
            Database::execute("DELETE FROM transfers WHERE id = ?", [$t['id']]);
            $approved++;
        }

        Response::success(['approved' => $approved], "$approved transfer(s) approved");
    }

    public function bulkTransferToDP(Request $req): void
    {
        $data = $req->json();
        $ids  = $data['ids']                    ?? [];
        $dpId = $data['distribution_point_id']  ?? null;

        if (empty($ids)) Response::error('ids are required');
        if (!$dpId)      Response::error('distribution_point_id is required');

        $created = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device) continue;

            // Reject invalid statuses
            if (in_array($device['status'], ['OFFLINE', 'LOST', 'DAMAGED'])) continue;
            // Skip if already has a distribution point
            if (!empty($device['distribution_point_id'])) continue;

            Transfer::create([
                'device_id'                     => $deviceId,
                'device_serial'                 => $device['serial_number'] ?? $device['device_id'],
                'transfer_type'                 => 'DISTRIBUTION',
                'transfer_status'               => 'PENDING',
                'to_distribution_point_id'      => $dpId,
                'original_allocation_point_id'  => $device['allocation_point_id'],
                'original_distribution_point_id'=> $device['distribution_point_id'],
                'original_status'               => $device['status'],
                'quantity'                      => 1,
            ]);
            $created++;
        }

        Response::success(['created' => $created], "$created transfer record(s) created");
    }
}
