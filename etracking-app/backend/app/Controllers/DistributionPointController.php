<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\DistributionPoint;
use App\Models\Transfer;
use App\Models\Device;

class DistributionPointController
{
    public function index(Request $req): void
    {
        Response::success(DistributionPoint::allWithCounts());
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = DistributionPoint::find($id);
        if (!$row) Response::notFound('Distribution point not found');
        Response::success($row);
    }

    public function devices(Request $req): void
    {
        $id      = (int) $req->param('id');
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = (int) $req->query('per_page', 25);
        $filters = ['status' => $req->query('status'), 'search' => $req->query('search')];

        $result = DistributionPoint::findWithDevices($id, $page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function statusCounts(Request $req): void
    {
        $id   = (int) $req->param('id');
        $rows = Database::query(
            "SELECT status, COUNT(*) as cnt FROM devices WHERE distribution_point_id = ? GROUP BY status",
            [$id]
        );
        $counts = [];
        foreach ($rows as $r) $counts[$r['status']] = (int) $r['cnt'];
        Response::success($counts);
    }

    public function acceptDevices(Request $req): void
    {
        $dpId = (int) $req->param('id');
        $data = $req->json();
        $ids  = $data['device_ids'] ?? [];

        if (empty($ids)) Response::error('device_ids are required');

        $updated = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device || $device['status'] !== 'RECEIVED') continue;
            if ($device['distribution_point_id'] != $dpId) continue;

            $prevStatus = $device['original_status'] ?? 'ONLINE';
            if (!$prevStatus || $prevStatus === 'RECEIVED') $prevStatus = 'ONLINE';

            Database::execute(
                'UPDATE devices SET status = ?, allocation_point_id = NULL, updated_at = NOW() WHERE id = ?',
                [$prevStatus, $deviceId]
            );
            $updated++;
        }

        Response::success(['updated' => $updated], "$updated device(s) accepted");
    }

    public function sendToAllocationPoint(Request $req): void
    {
        $dpId = (int) $req->param('id');
        $data = $req->json();
        $ids  = $data['device_ids']        ?? [];
        $apId = $data['allocation_point_id'] ?? null;

        if (empty($ids)) Response::error('device_ids are required');
        if (!$apId)      Response::error('allocation_point_id is required');

        $ap = Database::queryOne('SELECT id, name FROM allocation_points WHERE id = ?', [(int) $apId]);
        if (!$ap) Response::error('Allocation point not found', 404);
        $apName = trim($ap['name']);

        $created    = 0;
        $mismatches = [];
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device) continue;
            if ($device['status'] === 'RECEIVED') continue;

            // Enforce AP name must match device Company name
            if (!empty($device['company_id'])) {
                $company = Database::queryOne('SELECT name FROM companies WHERE id = ?', [(int) $device['company_id']]);
                $companyName = $company ? trim($company['name']) : '';
                if ($companyName !== '' && strcasecmp($apName, $companyName) !== 0) {
                    $mismatches[] = $device['device_id'] . " (Company: {$companyName})";
                    continue;
                }
            }

            Transfer::create([
                'device_id'                  => $deviceId,
                'device_serial'              => $device['serial_number'] ?? $device['device_id'],
                'transfer_type'              => 'ALLOCATION',
                'transfer_status'            => 'COMPLETED',
                'from_distribution_point_id' => $dpId,
                'to_allocation_point_id'     => $apId,
                'original_status'            => $device['status'],
                'quantity'                   => 1,
            ]);

            Database::execute(
                'UPDATE devices SET allocation_point_id = ?, distribution_point_id = NULL, status = ?, updated_at = NOW() WHERE id = ?',
                [$apId, 'RECEIVED', $deviceId]
            );
            $created++;
        }

        if ($created === 0 && !empty($mismatches)) {
            Response::error(
                'Transfer rejected: Allocation Point "' . $apName . '" does not match the Company for device(s): ' . implode(', ', $mismatches) . '. The AP name must match the device Company name.',
                422
            );
        }

        $msg = "$created device(s) sent to allocation point";
        if (!empty($mismatches)) {
            $msg .= '. Skipped ' . count($mismatches) . ' device(s) due to AP/Company name mismatch: ' . implode(', ', $mismatches);
        }
        Response::success(['sent' => $created, 'mismatches' => $mismatches], $msg);
    }

    public function returnToInventory(Request $req): void
    {
        $dpId = (int) $req->param('id');
        $data = $req->json();
        $ids  = $data['device_ids'] ?? [];

        if (empty($ids)) Response::error('device_ids are required');

        $processed = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device) continue;
            if ($device['status'] === 'RECEIVED') continue;

            Transfer::create([
                'device_id'                  => $deviceId,
                'device_serial'              => $device['serial_number'] ?? $device['device_id'],
                'transfer_type'              => 'DISTRIBUTION',
                'transfer_status'            => 'COMPLETED',
                'from_distribution_point_id' => $dpId,
                'original_status'            => $device['status'],
                'quantity'                   => 1,
            ]);

            Database::execute(
                'UPDATE devices SET distribution_point_id = NULL, allocation_point_id = NULL, updated_at = NOW() WHERE id = ?',
                [$deviceId]
            );
            $processed++;
        }

        Response::success(['returned' => $processed], "$processed device(s) returned to inventory");
    }

    public function acceptReturned(Request $req): void
    {
        $dpId = (int) $req->param('id');
        $data = $req->json();
        $ids  = $data['device_ids'] ?? [];

        if (empty($ids)) Response::error('device_ids are required');

        $processed = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device || $device['status'] !== 'PENDING') continue;
            if ($device['distribution_point_id'] != $dpId) continue;

            $transfer = Database::query(
                "SELECT * FROM transfers WHERE device_id = ? ORDER BY created_at DESC LIMIT 1",
                [$deviceId]
            );
            $restoreStatus = ($transfer[0]['original_status'] ?? null) ?: 'ONLINE';

            Database::execute(
                'UPDATE devices SET status = ?, distribution_point_id = ?, updated_at = NOW() WHERE id = ?',
                [$restoreStatus, $dpId, $deviceId]
            );

            Database::execute(
                "DELETE FROM device_retrievals WHERE device_id = ? AND is_archived = FALSE",
                [$deviceId]
            );
            $processed++;
        }

        Response::success(['accepted' => $processed], "$processed device(s) accepted from field");
    }

    public function rejectDevices(Request $req): void
    {
        $dpId = (int) $req->param('id');
        $data = $req->json();
        $ids  = $data['device_ids'] ?? [];

        if (empty($ids)) Response::error('device_ids are required');

        $processed = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device || $device['status'] !== 'PENDING') continue;
            if ($device['distribution_point_id'] != $dpId) continue;

            $retrieval = Database::query(
                "SELECT * FROM device_retrievals WHERE device_id = ? AND retrieval_status = 'RETURNED' ORDER BY created_at DESC LIMIT 1",
                [$deviceId]
            );

            Database::execute(
                'UPDATE devices SET status = ?, distribution_point_id = NULL, updated_at = NOW() WHERE id = ?',
                ['RETRIEVED', $deviceId]
            );

            if (!empty($retrieval)) {
                Database::execute(
                    "UPDATE device_retrievals SET retrieval_status = 'RETRIEVED', transfer_status = 'pending', updated_at = NOW() WHERE id = ?",
                    [$retrieval[0]['id']]
                );
            }
            $processed++;
        }

        Response::success(['rejected' => $processed], "$processed device(s) rejected");
    }

    public function changeStatus(Request $req): void
    {
        $dpId   = (int) $req->param('id');
        $data   = $req->json();
        $ids    = $data['device_ids'] ?? [];
        $status = $data['status']     ?? null;

        if (empty($ids)) Response::error('device_ids are required');
        if (!$status)    Response::error('status is required');

        $places  = implode(',', array_fill(0, count($ids), '?'));
        $updated = Database::execute(
            "UPDATE devices SET status = ?, updated_at = NOW() WHERE id IN ($places) AND distribution_point_id = ? AND status != 'RECEIVED'",
            [$status, ...$ids, $dpId]
        );

        Response::success(['updated' => $updated], "$updated device(s) status changed");
    }

    public function sendToAnotherDP(Request $req): void
    {
        $dpId       = (int) $req->param('id');
        $data       = $req->json();
        $ids        = $data['device_ids']              ?? [];
        $targetDpId = $data['target_distribution_point_id'] ?? null;

        if (empty($ids))  Response::error('device_ids are required');
        if (!$targetDpId) Response::error('target_distribution_point_id is required');

        $processed = 0;
        foreach ($ids as $deviceId) {
            $device = Device::find((int) $deviceId);
            if (!$device || $device['status'] === 'RECEIVED') continue;

            // Check no pending transfers
            $pending = Database::query(
                "SELECT id FROM transfers WHERE device_id = ? AND transfer_status = 'PENDING'",
                [$deviceId]
            );
            if (!empty($pending)) continue;

            Transfer::create([
                'device_id'                  => $deviceId,
                'device_serial'              => $device['serial_number'] ?? $device['device_id'],
                'transfer_type'              => 'DISTRIBUTION',
                'transfer_status'            => 'COMPLETED',
                'from_distribution_point_id' => $dpId,
                'to_distribution_point_id'   => $targetDpId,
                'original_status'            => $device['status'],
                'quantity'                   => 1,
            ]);

            Database::execute(
                'UPDATE devices SET distribution_point_id = ?, status = ?, updated_at = NOW() WHERE id = ?',
                [$targetDpId, 'RECEIVED', $deviceId]
            );
            $processed++;
        }

        Response::success(['sent' => $processed], "$processed device(s) sent to another distribution point");
    }

    public function store(Request $req): void
    {
        $data = $req->validated(['name' => 'required', 'location' => 'required']);
        $row  = DistributionPoint::create($data);
        Response::success($row, 'Distribution point created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        DistributionPoint::findOrFail($id);
        $row = DistributionPoint::update($id, $data);
        Response::success($row, 'Distribution point updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        DistributionPoint::findOrFail($id);
        DistributionPoint::delete($id);
        Response::success(null, 'Distribution point deleted');
    }
}
