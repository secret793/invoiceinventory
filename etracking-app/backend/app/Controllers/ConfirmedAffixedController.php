<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\ConfirmedAffixed;
use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use App\Models\ConfirmedAffixLog;
use App\Services\PermissionService;
use App\Services\OverstayCalculatorService;

class ConfirmedAffixedController
{
    /** Columns that actually exist in the confirmed_affixeds table */
    private const ALLOWED = [
        'device_id', 'allocation_point_id', 'boe', 'sad_number', 'transaction_type',
        'transaction_reference', 'vehicle_number', 'truck_number', 'driver_name',
        'regime', 'destination', 'destination_id', 'route_id', 'long_route_id',
        'manifest_date', 'agency', 'agent_contact', 'receipt_id', 'status', 'date',
    ];

    /** Nullable bigint FK columns — empty string must become NULL */
    private const INT_NULLABLE = [
        'device_id', 'allocation_point_id', 'destination_id',
        'route_id', 'long_route_id', 'receipt_id',
    ];

    private static function sanitize(array $data): array
    {
        $clean = array_intersect_key($data, array_flip(self::ALLOWED));
        foreach (self::INT_NULLABLE as $col) {
            if (array_key_exists($col, $clean) && ($clean[$col] === '' || $clean[$col] === null)) {
                $clean[$col] = null;
            }
        }
        // manifest_date: empty string → null (it's a date column)
        if (array_key_exists('manifest_date', $clean) && $clean['manifest_date'] === '') {
            $clean['manifest_date'] = null;
        }
        return $clean;
    }

    public function index(Request $req): void
    {
        $user    = $req->user();
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));

        $filters        = ['status' => $req->query('status'), 'search' => $req->query('search')];
        $permittedApIds   = [];
        $permittedDestIds = [];

        // Non-admin: filter by permitted allocation points
        if (!PermissionService::isSuperAdmin($user) && !PermissionService::hasRole($user, 'Warehouse Manager')) {
            $permitted = PermissionService::filterAllocationPointIds($user);
            if ($permitted !== null) $permittedApIds = $permitted;
        }

        // Affixing Officers + Read Only Tracker: also filter by destination permissions
        if (PermissionService::hasAnyRole($user, ['Affixing Officer', 'Read Only Tracker Officer'])
            && !PermissionService::isSuperAdmin($user)
            && !PermissionService::hasRole($user, 'Warehouse Manager')) {
            $destIds = PermissionService::filterDestinationIds($user);
            // null = no filter; empty array = no permissions, force zero results
            $permittedDestIds = $destIds ?? [];
            if ($destIds !== null && count($destIds) === 0) {
                $permittedDestIds = [-1]; // forces WHERE destination_id IN (-1) → 0 rows
            }
        }

        $result = ConfirmedAffixed::listPaginated($page, $perPage, $filters, $permittedApIds, $permittedDestIds);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        Response::success(ConfirmedAffixed::findOrFail((int) $req->param('id')));
    }

    public function store(Request $req): void
    {
        $raw = $req->json();

        if (empty($raw['boe']))            Response::error('boe is required', 422);
        if (empty($raw['vehicle_number'])) Response::error('vehicle_number is required', 422);
        if (empty($raw['device_id']))      Response::error('device_id is required', 422);

        $data = self::sanitize($raw);

        // ── Guard: receipt must exist and still have quota ─────────────────
        if (!empty($data['receipt_id'])) {
            $receipt = Database::queryOne(
                'SELECT id, receipt_number, used FROM receipts WHERE id = ?',
                [$data['receipt_id']]
            );
            if (!$receipt) {
                Response::error('Receipt not found', 422);
            }
            if ((int) $receipt['used'] <= 0) {
                Response::error("Receipt {$receipt['receipt_number']} is fully used (0 slots remaining)", 422);
            }
        }

        // ── Guard: device must not already be dispatched ──────────────────
        $already = Database::queryOne(
            'SELECT id FROM assign_to_agents WHERE device_id = ?',
            [$data['device_id']]
        );
        if ($already) {
            Response::error('This device is already dispatched. Return it before dispatching again.', 422);
        }

        // ── Create ConfirmedAffixed record ────────────────────────────────
        $row = ConfirmedAffixed::create($data);

        // ── Create assign_to_agents record (dispatch twin) ────────────────
        Database::execute(
            'INSERT INTO assign_to_agents (device_id, allocation_point_id, receipt_id, date, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())',
            [
                $data['device_id'],
                $data['allocation_point_id'] ?? null,
                $data['receipt_id']          ?? null,
                $data['date']                ?? date('Y-m-d H:i:s'),
            ]
        );

        // ── Decrement receipt used counter ────────────────────────────────
        if (!empty($data['receipt_id'])) {
            Database::execute(
                'UPDATE receipts SET used = GREATEST(0, used - 1), updated_at = NOW() WHERE id = ?',
                [$data['receipt_id']]
            );
        }

        // ── Clear device from allocation point (device leaves the AP) ─────
        Database::execute(
            'UPDATE devices SET allocation_point_id = NULL, updated_at = NOW() WHERE id = ?',
            [$data['device_id']]
        );

        Response::success($row, 'Device dispatched successfully', 201);
    }

    public function pickForAffixing(Request $req): void
    {
        $id       = (int) $req->param('id');
        $ca       = ConfirmedAffixed::findOrFail($id);
        $user     = $req->user();
        $affixDate = $req->input('affixing_date', date('Y-m-d H:i:s'));

        // 1. Create DeviceRetrieval
        $retrievalData = array_merge(array_intersect_key($ca, array_flip([
            'device_id', 'boe', 'sad_number', 'transaction_type', 'transaction_reference',
            'vehicle_number', 'regime', 'destination', 'destination_id', 'route_id',
            'long_route_id', 'manifest_date', 'agency', 'agent_contact',
            'truck_number', 'driver_name', 'allocation_point_id', 'receipt_id',
        ])), [
            'date'             => $ca['date'],
            'affixing_date'    => $affixDate,
            'status'           => 'pending',
            'retrieval_status' => 'NOT_RETRIEVED',
            'transfer_status'  => 'pending',
            'overstay_days'    => 0,
            'overstay_amount'  => 0,
            'payment_status'   => 'PP',
            'user_id'          => $user['id'],
        ]);
        $retrieval = DeviceRetrieval::create($retrievalData);

        // 2. Create Monitoring record (monitorings table has no sad_number column)
        Monitoring::create(array_merge(array_intersect_key($ca, array_flip([
            'device_id', 'boe', 'vehicle_number', 'regime',
            'destination', 'route_id', 'long_route_id', 'manifest_date',
            'agency', 'agent_contact', 'truck_number', 'driver_name',
            'allocation_point_id',
        ])), [
            'date'             => $ca['date'],
            'affixing_date'    => $affixDate,
            'status'           => 'ACTIVE',
            'overstay_days'    => 0,
            'retrieval_status' => 'NOT_RETRIEVED',
        ]));

        // 3. Create ConfirmedAffixLog
        ConfirmedAffixLog::create([
            'device_id'           => $ca['device_id'],
            'boe'                 => $ca['boe'],
            'vehicle_number'      => $ca['vehicle_number'],
            'allocation_point_id' => $ca['allocation_point_id'],
            'affixing_date'       => $affixDate,
            'affixed_by'          => $user['id'],
            'status'              => 'AFFIXED',
            'confirmed_affixed_id' => $ca['id'],
        ]);

        // 4. Delete from assign_to_agents (if linked)
        Database::execute(
            'DELETE FROM assign_to_agents WHERE receipt_id = ? OR (device_id = ? AND date = ?)',
            [$ca['receipt_id'], $ca['device_id'], $ca['date']]
        );

        // 5. Delete the ConfirmedAffixed record
        ConfirmedAffixed::delete($id);

        Response::success($retrieval, 'Device picked for affixing. Retrieval and monitoring records created.');
    }

    public function returnData(Request $req): void
    {
        $id   = (int) $req->param('id');
        $ca   = ConfirmedAffixed::findOrFail($id);
        $note = $req->input('return_note', '');

        if (!$note) Response::error('return_note is required');

        // Update related monitoring
        Database::execute(
            "UPDATE monitorings SET note = ?, retrieval_status = 'RETURNED', updated_at = NOW() WHERE device_id = ?",
            [$note, $ca['device_id']]
        );
        Database::execute(
            "UPDATE device_retrievals SET note = ?, retrieval_status = 'RETURNED', updated_at = NOW() WHERE device_id = ?",
            [$note, $ca['device_id']]
        );
        Database::execute(
            "UPDATE data_entry_assignments SET status = 'RETURNED', return_note = ?, updated_at = NOW() WHERE device_id = ?",
            [$note, $ca['device_id']]
        );

        // ── Restore receipt used counter (Eloquent observer equivalent) ───
        $ata = Database::queryOne(
            'SELECT receipt_id FROM assign_to_agents WHERE device_id = ? ORDER BY id DESC LIMIT 1',
            [$ca['device_id']]
        );
        if ($ata && !empty($ata['receipt_id'])) {
            Database::execute(
                'UPDATE receipts SET used = used + 1, updated_at = NOW() WHERE id = ?',
                [$ata['receipt_id']]
            );
        }

        // ── Delete assign_to_agents record ────────────────────────────────
        Database::execute(
            'DELETE FROM assign_to_agents WHERE device_id = ?',
            [$ca['device_id']]
        );

        // ── Restore device to original allocation point (status → ONLINE) ─
        if (!empty($ca['allocation_point_id'])) {
            Database::execute(
                "UPDATE devices SET allocation_point_id = ?, status = 'ONLINE', updated_at = NOW() WHERE id = ?",
                [$ca['allocation_point_id'], $ca['device_id']]
            );
        }

        // ── Delete confirmed affixed record ────────────────────────────────
        ConfirmedAffixed::delete($id);

        Response::success(null, 'Data returned successfully. Device restored to allocation point.');
    }

    public function report(Request $req): void
    {
        $filters = [
            'search'              => $req->query('search'),
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'allocation_point_id' => $req->query('allocation_point_id'),
            'status'              => $req->query('status'),
        ];

        $rows = ConfirmedAffixed::reportData($filters);
        Response::success($rows);
    }

    public function exportReport(Request $req): void
    {
        $filters = [
            'from'   => $req->query('from'),
            'to'     => $req->query('to'),
            'status' => $req->query('status'),
        ];
        $rows = ConfirmedAffixed::reportData($filters);
        \App\Services\ExportService::streamCsv($rows, 'confirmed-affix-report-' . date('Ymd') . '.csv');
    }
}
