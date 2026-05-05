<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use App\Services\PermissionService;
use App\Services\OverstayCalculatorService;

class DeviceRetrievalController
{
    public function index(Request $req): void
    {
        $user    = $req->user();
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));

        $filters = [
            'status'              => $req->query('status'),
            'retrieval_status'    => $req->query('retrieval_status'),
            'payment_status'      => $req->query('payment_status'),
            'allocation_point_id' => $req->query('allocation_point_id'),
            'destination_id'      => $req->query('destination_id'),
            'route_type'          => $req->query('route_type'),
            'overdue'             => $req->query('overdue'),
            'overstay_min'        => $req->query('overstay_min'),
            'overstay_max'        => $req->query('overstay_max'),
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'search'              => $req->query('search'),
        ];

        // Role-based destination filter for Retrieval Officers
        $permittedDestIds = [];
        if (!PermissionService::isSuperAdmin($user)
            && !PermissionService::hasRole($user, 'Warehouse Manager')
            && !PermissionService::hasRole($user, 'Finance Officer')) {
            $filtered = PermissionService::filterDestinationIds($user);
            if ($filtered !== null) $permittedDestIds = $filtered;
        }

        $result = DeviceRetrieval::listPaginated($page, $perPage, $filters, $permittedDestIds);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        Response::success(DeviceRetrieval::findOrFail((int) $req->param('id')));
    }

    public function store(Request $req): void
    {
        $data = $req->json();
        $row  = DeviceRetrieval::create($data);
        Response::success($row, 'Retrieval record created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        $user = $req->user();

        DeviceRetrieval::findOrFail($id);

        // Finance Officer can only update finance fields
        if (PermissionService::hasRole($user, 'Finance Officer')
            && !PermissionService::isSuperAdmin($user)) {
            $data = array_intersect_key($data, array_flip([
                'finance_approval_date', 'finance_approved_by', 'finance_notes',
                'payment_status', 'overstay_amount',
            ]));
        }

        $row = DeviceRetrieval::update($id, $data);

        // Sync retrieval_status to monitoring
        if (isset($data['retrieval_status'])) {
            Database::execute(
                'UPDATE monitorings SET retrieval_status = ?, updated_at = NOW() WHERE device_id = ?',
                [$data['retrieval_status'], $row['device_id']]
            );
        }

        Response::success($row, 'Retrieval updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        DeviceRetrieval::findOrFail($id);
        DeviceRetrieval::delete($id);
        Response::success(null, 'Retrieval deleted');
    }

    public function report(Request $req): void
    {
        $filters = [
            'search'              => $req->query('search'),
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'retrieval_status'    => $req->query('retrieval_status'),
            'allocation_point_id' => $req->query('allocation_point_id'),
        ];
        Response::success(DeviceRetrieval::reportData($filters));
    }

    public function exportReport(Request $req): void
    {
        $filters = [
            'from'             => $req->query('from'),
            'to'               => $req->query('to'),
            'retrieval_status' => $req->query('retrieval_status'),
        ];
        $rows = DeviceRetrieval::reportData($filters);
        \App\Services\ExportService::streamCsv($rows, 'device-retrieval-report-' . date('Ymd') . '.csv');
    }

    public function generateInvoice(Request $req): void
    {
        $id       = (int) $req->param('id');
        $retrieval = DeviceRetrieval::findOrFail($id);

        $calc = OverstayCalculatorService::calculate($retrieval);

        // Update retrieval with fresh overstay calc
        DeviceRetrieval::update($id, [
            'overstay_days'   => $calc['overstay_days'],
            'overstay_amount' => $calc['overstay_amount'],
            'overdue_hours'   => $calc['overdue_hours'],
        ]);

        // Create or update invoice
        $existing = \App\Models\Invoice::findByRetrieval($id);
        $invoiceData = [
            'device_retrieval_id' => $id,
            'device_id'           => $retrieval['device_id'],
            'boe'                 => $retrieval['boe'],
            'vehicle_number'      => $retrieval['vehicle_number'],
            'overstay_days'       => $calc['overstay_days'],
            'overstay_amount'     => $calc['overstay_amount'],
            'exchange_rate'       => $calc['exchange_rate'],
            'status'              => 'PENDING',
        ];

        if ($existing) {
            $invoice = \App\Models\Invoice::update((int) $existing['id'], $invoiceData);
        } else {
            $invoice = \App\Models\Invoice::create($invoiceData);
        }

        Response::success(array_merge($invoice, $calc), 'Invoice generated');
    }

    public function invoice(Request $req): void
    {
        $id      = (int) $req->param('id');
        $invoice = \App\Models\Invoice::findByRetrieval($id);
        if (!$invoice) Response::notFound('No invoice found for this retrieval');
        Response::success($invoice);
    }
}
