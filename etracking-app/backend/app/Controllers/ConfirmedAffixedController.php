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

        $filters       = ['status' => $req->query('status'), 'search' => $req->query('search')];
        $permittedApIds = [];

        // Non-admin: filter by permitted allocation points
        if (!PermissionService::isSuperAdmin($user) && !PermissionService::hasRole($user, 'Warehouse Manager')) {
            $permitted = PermissionService::filterAllocationPointIds($user);
            if ($permitted !== null) $permittedApIds = $permitted;
        }

        $result = ConfirmedAffixed::listPaginated($page, $perPage, $filters, $permittedApIds);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        Response::success(ConfirmedAffixed::findOrFail((int) $req->param('id')));
    }

    public function store(Request $req): void
    {
        $raw = $req->json();

        if (empty($raw['boe'])) {
            Response::error('boe is required', 422);
        }
        if (empty($raw['vehicle_number'])) {
            Response::error('vehicle_number is required', 422);
        }

        $data = self::sanitize($raw);
        $row  = ConfirmedAffixed::create($data);
        Response::success($row, 'Confirmed affixed record created', 201);
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

        // Update related monitoring (use parameterised values — PostgreSQL rejects double-quoted literals)
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

        // Delete confirmed affixed
        ConfirmedAffixed::delete($id);

        Response::success(null, 'Data returned successfully');
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
