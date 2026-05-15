<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\DeviceRetrieval;
use App\Models\WaiverHistory;
use App\Models\Receipt;
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

        if (PermissionService::hasRole($user, 'Finance Officer') && !PermissionService::isSuperAdmin($user)) {
            $filters['finance_only'] = true;
        }

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

        if (PermissionService::hasRole($user, 'Finance Officer') && !PermissionService::isSuperAdmin($user)) {
            $data = array_intersect_key($data, array_flip([
                'finance_approval_date', 'finance_approved_by', 'finance_notes',
                'payment_status', 'overstay_amount', 'receipt_number',
            ]));
        }

        $row = DeviceRetrieval::update($id, $data);

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

    public function retrieve(Request $req): void
    {
        $id       = (int) $req->param('id');
        $data     = $req->json();
        $user     = $req->user();
        $retrieval = DeviceRetrieval::findOrFail($id);

        if ($retrieval['retrieval_status'] === 'RETRIEVED') {
            Response::error('Device already retrieved.');
        }

        $overstayDays = (int) ($retrieval['overstay_days'] ?? 0);
        $payStatus    = $retrieval['payment_status'] ?? 'PP';
        $isWaived     = WaiverHistory::existsForRetrieval($id);

        $canRetrieve = $isWaived
            || $overstayDays < 1
            || ($overstayDays >= 1 && $payStatus === 'PD');

        if (!$canRetrieve) {
            Response::error('Overstay bill must be paid or waived before device retrieval.', 422);
        }

        $type          = strtoupper($retrieval['transaction_type'] ?? '');
        $isSAD         = in_array($type, ['SAD', 'T1'], true);
        $receiptId     = isset($retrieval['receipt_id']) ? (int) $retrieval['receipt_id'] : null;
        $t1Ref         = trim($data['t1_validation_ref'] ?? '');
        $receiptNum    = trim($data['receipt_number']    ?? '');

        /* null receipt_id on a SAD record = legacy — treat as last device */
        $isLastDevice  = $isSAD
            ? ($receiptId ? Receipt::isLastDeviceForReceipt($receiptId, $id) : true)
            : false;

        if ($isSAD && $isLastDevice && $t1Ref === '') {
            Response::error('T1 Validation Reference is required for the last device on a SAD receipt.', 422);
        }

        $isPrivileged = PermissionService::isSuperAdmin($user)
            || PermissionService::hasRole($user, 'Warehouse Manager');

        if (!$isPrivileged && $overstayDays >= 1 && $receiptNum === '') {
            Response::error('Receipt Number is required before retrieval when device is overdue.', 422);
        }

        Database::execute(
            "UPDATE device_retrievals
             SET retrieval_status    = 'RETRIEVED',
                 t1_validation_ref   = COALESCE(NULLIF(?, ''), t1_validation_ref),
                 receipt_number      = COALESCE(NULLIF(?, ''), receipt_number),
                 updated_at          = NOW()
             WHERE id = ?",
            [$t1Ref, $receiptNum, $id]
        );

        Database::execute(
            "UPDATE devices SET status = 'RETRIEVED', updated_at = NOW() WHERE id = ?",
            [$retrieval['device_id']]
        );

        Database::execute(
            "UPDATE monitorings SET retrieval_status = 'RETRIEVED', updated_at = NOW() WHERE device_id = ?",
            [$retrieval['device_id']]
        );

        Database::execute(
            'INSERT INTO device_retrieval_logs (device_id, device_retrieval_id, boe, action_type, performed_by, notes)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$retrieval['device_id'], $id, $retrieval['boe'], 'RETRIEVED', $user['id'], $t1Ref ? "T1: $t1Ref" : null]
        );

        Response::success(DeviceRetrieval::find($id), 'Device retrieved successfully.');
    }

    public function returnToOutstation(Request $req): void
    {
        $id       = (int) $req->param('id');
        $data     = $req->json();
        $user     = $req->user();
        $dpId     = $data['distribution_point_id'] ?? null;
        $reason   = $data['archive_reason'] ?? null;
        $retrieval = DeviceRetrieval::findOrFail($id);

        if (!$dpId) Response::error('distribution_point_id is required.', 422);

        if ($retrieval['retrieval_status'] !== 'RETRIEVED') {
            Response::error('Only retrieved devices can be returned to outstation.', 422);
        }

        if (($retrieval['transfer_status'] ?? '') === 'completed') {
            Response::error('Transfer is already completed.', 422);
        }

        Database::execute(
            "UPDATE devices SET status = 'PENDING', distribution_point_id = ?, updated_at = NOW() WHERE id = ?",
            [$dpId, $retrieval['device_id']]
        );

        Database::execute(
            "UPDATE device_retrievals
             SET retrieval_status = 'RETURNED',
                 transfer_status = 'pending',
                 distribution_point_id = ?,
                 archive_reason = ?,
                 is_archived = TRUE,
                 archived_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            [$dpId, $reason, $id]
        );

        Database::execute(
            'DELETE FROM monitorings WHERE device_id = ?',
            [$retrieval['device_id']]
        );

        Database::execute(
            'INSERT INTO device_retrieval_logs (device_id, device_retrieval_id, boe, action_type, performed_by, notes)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$retrieval['device_id'], $id, $retrieval['boe'], 'RETURNED_OUTSTATION', $user['id'], $reason]
        );

        Response::success(DeviceRetrieval::find($id), 'Device returned to outstation.');
    }

    public function checkLastDevice(Request $req): void
    {
        $id        = (int) $req->param('id');
        $retrieval = DeviceRetrieval::findOrFail($id);
        $type      = strtoupper($retrieval['transaction_type'] ?? '');
        $isSAD     = in_array($type, ['SAD', 'T1'], true);
        $receiptId = isset($retrieval['receipt_id']) ? (int) $retrieval['receipt_id'] : null;
        /* null receipt_id on SAD = legacy record → treat as last device */
        $isLast = $isSAD
            ? ($receiptId ? Receipt::isLastDeviceForReceipt($receiptId, $id) : true)
            : false;
        Response::success([
            'is_sad'         => $isSAD,
            'is_last_device' => $isLast,
            'receipt_id'     => $receiptId,
        ]);
    }

    public function generateInvoice(Request $req): void
    {
        $id        = (int) $req->param('id');
        $body      = $req->json();
        $retrieval = DeviceRetrieval::findOrFail($id);
        $calc      = OverstayCalculatorService::calculate($retrieval);

        if ($calc['overstay_days'] < 1) {
            Response::error('No overstay days to bill.', 422);
        }

        // Allow caller to override editable fields from the form
        $consignee     = trim($body['consignee']      ?? '');
        $referenceDate = trim($body['reference_date'] ?? '');
        $notes         = trim($body['notes']          ?? '');

        DeviceRetrieval::update($id, [
            'overstay_days'   => $calc['overstay_days'],
            'overstay_amount' => $calc['overstay_amount'],
            'overdue_hours'   => $calc['overdue_hours'],
            'payment_status'  => 'PP',
        ]);

        $existing    = \App\Models\Invoice::findByRetrieval($id);
        $invoiceData = \App\Models\Invoice::buildFromRetrieval($retrieval, $calc);

        if ($consignee !== '')     $invoiceData['consignee']      = $consignee;
        if ($referenceDate !== '') $invoiceData['reference_date'] = $referenceDate;
        if ($notes !== '')         $invoiceData['notes']          = $notes;

        if ($existing) {
            $existingId = (int) $existing['id'];
            unset($invoiceData['reference_number']);
            $invoice = \App\Models\Invoice::update($existingId, $invoiceData);
        } else {
            $invoice = \App\Models\Invoice::create($invoiceData);
        }

        Response::success(array_merge($invoice, $calc), 'Overstay bill generated.');
    }

    public function invoice(Request $req): void
    {
        $id      = (int) $req->param('id');
        $invoice = \App\Models\Invoice::findByRetrieval($id);
        if (!$invoice) Response::notFound('No invoice found for this retrieval.');
        Response::success($invoice);
    }

    public function downloadInvoice(Request $req): void
    {
        $id        = (int) $req->param('id');
        $retrieval = DeviceRetrieval::findOrFail($id);
        $invoice   = \App\Models\Invoice::findByRetrieval($id);

        if (!$invoice) Response::notFound('No invoice found.');

        if ($retrieval['payment_status'] !== 'PD' || empty($retrieval['finance_approval_date'])) {
            Response::error('Invoice is only available after Finance Officer approval.', 403);
        }

        $ref     = htmlspecialchars($invoice['reference_number'] ?? 'N/A');
        $boe     = htmlspecialchars($retrieval['boe'] ?? 'N/A');
        $vehicle = htmlspecialchars($retrieval['vehicle_number'] ?? 'N/A');
        $days    = (int) ($retrieval['overstay_days'] ?? 0);
        $amount  = number_format((float)($retrieval['overstay_amount'] ?? 0), 2);
        $approvedDate = $retrieval['finance_approval_date'] ? date('d M Y', strtotime($retrieval['finance_approval_date'])) : 'N/A';
        $receiptNo = htmlspecialchars($retrieval['receipt_number'] ?? 'N/A');
        $notes   = htmlspecialchars($retrieval['finance_notes'] ?? '');
        $regime  = htmlspecialchars($retrieval['regime'] ?? 'N/A');
        $driver  = htmlspecialchars($retrieval['driver_name'] ?? 'N/A');
        $sad     = htmlspecialchars($retrieval['sad_number'] ?? 'N/A');

        header('Content-Type: text/html; charset=utf-8');
        header("Content-Disposition: inline; filename=\"Invoice-{$ref}.html\"");
        echo <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>Invoice {$ref}</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; color: #222; }
  h1   { color: #1E2D7A; border-bottom: 3px solid #1E2D7A; padding-bottom: 8px; }
  .badge { background: #085E37; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 13px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  td, th { padding: 10px 14px; border: 1px solid #ddd; }
  th { background: #1E2D7A; color: #fff; text-align: left; }
  .amount { font-size: 22px; font-weight: bold; color: #E31E24; }
  @media print { button { display: none; } }
</style>
</head><body>
<h1>GNSW Overstay Invoice</h1>
<p>Reference: <strong>{$ref}</strong> &nbsp; <span class="badge">PAID</span></p>
<table>
<tr><th colspan="2">Shipment Details</th></tr>
<tr><td>BOE / Declaration No.</td><td><strong>{$boe}</strong></td></tr>
<tr><td>SAD Number</td><td>{$sad}</td></tr>
<tr><td>Vehicle Number</td><td>{$vehicle}</td></tr>
<tr><td>Driver / Agent</td><td>{$driver}</td></tr>
<tr><td>Regime</td><td>{$regime}</td></tr>
<tr><th colspan="2">Overstay Charges</th></tr>
<tr><td>Overstay Days</td><td><strong>{$days} day(s)</strong></td></tr>
<tr><td>Rate per Day</td><td>GMD 1,000.00</td></tr>
<tr><td>Total Overstay Amount</td><td><span class="amount">GMD {$amount}</span></td></tr>
<tr><th colspan="2">Finance Approval</th></tr>
<tr><td>Approval Date</td><td>{$approvedDate}</td></tr>
<tr><td>Receipt Number</td><td>{$receiptNo}</td></tr>
<tr><td>Notes</td><td>{$notes}</td></tr>
</table>
<p style="margin-top:24px; font-size:12px; color:#888;">Generated by GNSW E-Tracking System &mdash; {$ref}</p>
<button onclick="window.print()" style="margin-top:16px;padding:10px 24px;background:#1E2D7A;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:15px;">Print / Save as PDF</button>
</body></html>
HTML;
        exit;
    }

    public function waiver(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        $user = $req->user();

        if (!PermissionService::isSuperAdmin($user) && !PermissionService::hasRole($user, 'Admin')) {
            Response::error('Only Super Admin or Admin can waive overstay fees.', 403);
        }

        $retrieval = DeviceRetrieval::findOrFail($id);

        if (WaiverHistory::existsForRetrieval($id)) {
            Response::error('Overstay has already been waived for this record.', 422);
        }

        if ((int)($retrieval['overstay_days'] ?? 0) < 1) {
            Response::error('No overstay to waive.', 422);
        }

        $reason = trim($data['reason'] ?? '');
        if (empty($reason)) {
            Response::error('Waiver reason is required.', 422);
        }

        $invoice = \App\Models\Invoice::findByRetrieval($id);

        WaiverHistory::create([
            'device_retrieval_id'   => $id,
            'invoice_id'            => $invoice ? (int) $invoice['id'] : null,
            'admin_user_id'         => $user['id'],
            'reason'                => $reason,
            'original_overstay_days' => (int) ($retrieval['overstay_days'] ?? 0),
            'original_amount'       => (float) ($retrieval['overstay_amount'] ?? 0),
        ]);

        DeviceRetrieval::update($id, [
            'payment_status'  => 'WAIVED',
            'overstay_days'   => 0,
            'overstay_amount' => 0,
        ]);

        if ($invoice) {
            \App\Models\Invoice::update((int) $invoice['id'], [
                'status'   => 'WAIVED',
                'waived_by' => $user['id'],
                'waived_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Response::success(DeviceRetrieval::find($id), 'Overstay fee waived successfully.');
    }

    public function approvePayment(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        $user = $req->user();

        if (!PermissionService::hasRole($user, 'Finance Officer') && !PermissionService::isSuperAdmin($user)) {
            Response::error('Only Finance Officers can approve payments.', 403);
        }

        $retrieval = DeviceRetrieval::findOrFail($id);

        if ((float)($retrieval['overstay_amount'] ?? 0) <= 0) {
            Response::error('No overstay amount to approve.', 422);
        }

        $receiptNumber = trim($data['receipt_number'] ?? '');
        $financeNotes  = trim($data['finance_notes'] ?? '');

        if (empty($receiptNumber)) {
            Response::error('Receipt number is required to approve payment.', 422);
        }

        DeviceRetrieval::update($id, [
            'payment_status'      => 'PD',
            'finance_approval_date' => date('Y-m-d'),
            'finance_approved_by' => $user['name'] ?? (string) $user['id'],
            'receipt_number'      => $receiptNumber,
            'finance_notes'       => $financeNotes ?: null,
        ]);

        $invoice = \App\Models\Invoice::findByRetrieval($id);
        if ($invoice) {
            \App\Models\Invoice::update((int) $invoice['id'], [
                'status'         => 'PAID',
                'receipt_number' => $receiptNumber,
                'finance_notes'  => $financeNotes ?: null,
                'approved_by'    => $user['id'],
                'approved_at'    => date('Y-m-d H:i:s'),
                'paid_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        Response::success(DeviceRetrieval::find($id), 'Payment approved successfully.');
    }

    public function manualOverstayDays(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        $user = $req->user();

        if (!PermissionService::isSuperAdmin($user)) {
            Response::error('Only Super Admin can manually set overstay days.', 403);
        }

        $retrieval    = DeviceRetrieval::findOrFail($id);
        $oldDays      = (int) ($retrieval['overstay_days'] ?? 0);
        $overstayDays = max(0, (int) ($data['overstay_days'] ?? 0));
        $overstayAmt  = $overstayDays * 1000;

        DeviceRetrieval::update($id, [
            'overstay_days'   => $overstayDays,
            'overstay_amount' => $overstayAmt,
            'overdue_hours'   => $overstayDays * 24,
        ]);

        $deviceId = $retrieval['device_id'] ?? $id;
        $msg = "Device {$deviceId}: Updated from {$oldDays} to {$overstayDays} days.";
        Response::success(array_merge(DeviceRetrieval::find($id), ['old_days' => $oldDays]), $msg);
    }

    public function overstayDevices(Request $req): void
    {
        $filters = [
            'search'           => $req->query('search'),
            'device_id'        => $req->query('device_id'),
            'boe'              => $req->query('boe'),
            'invoice_number'   => $req->query('invoice_number'),
            'destination'      => $req->query('destination'),
            'allocation_point' => $req->query('allocation_point'),
            'payment_status'   => $req->query('payment_status'),
            'amount_min'       => $req->query('amount_min'),
            'amount_max'       => $req->query('amount_max'),
            'overstay_min'     => $req->query('overstay_min'),
            'overstay_max'     => $req->query('overstay_max'),
            'from'             => $req->query('from'),
            'to'               => $req->query('to'),
            'sort_by'          => $req->query('sort_by', 'dr.overstay_days'),
            'sort_dir'         => $req->query('sort_dir', 'DESC'),
        ];
        Response::success([
            'list'  => DeviceRetrieval::overstayList($filters),
            'stats' => DeviceRetrieval::overstayStats(),
        ]);
    }

    /* ── Report #1 — reads from device_retrieval_logs audit table ──────── */
    public function report(Request $req): void
    {
        $filters = [
            'search'              => $req->query('search'),
            'device_id'           => $req->query('device_id'),
            'boe'                 => $req->query('boe'),
            'vehicle_number'      => $req->query('vehicle_number'),
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'start_time'          => $req->query('start_time'),
            'end_time'            => $req->query('end_time'),
            'retrieval_status'    => $req->query('retrieval_status'),
            'action_type'         => $req->query('action_type'),
            'allocation_point_id' => $req->query('allocation_point_id'),
            'sort_by'             => $req->query('sort_by', 'l.created_at'),
            'sort_dir'            => $req->query('sort_dir', 'DESC'),
            'page'                => (int) $req->query('page', 1),
            'per_page'            => 25,
        ];
        Response::success(DeviceRetrieval::reportAuditLog($filters));
    }

    /* ── Report #1 CSV export ───────────────────────────────────────────── */
    public function exportReport(Request $req): void
    {
        $filters = [
            'search'           => $req->query('search'),
            'device_id'        => $req->query('device_id'),
            'boe'              => $req->query('boe'),
            'vehicle_number'   => $req->query('vehicle_number'),
            'from'             => $req->query('from'),
            'to'               => $req->query('to'),
            'retrieval_status' => $req->query('retrieval_status'),
            'action_type'      => $req->query('action_type'),
            'per_page'         => 5000,
            'page'             => 1,
        ];
        $result = DeviceRetrieval::reportAuditLog($filters);
        \App\Services\ExportService::streamCsv(
            $result['data'],
            'device-retrieval-audit-' . date('Ymd') . '.csv'
        );
    }

    /* ── Report #2 — financial/compliance view (retrieval_status required) */
    public function report2(Request $req): void
    {
        $retrieval_status = $req->query('retrieval_status');
        if (empty($retrieval_status)) {
            Response::error('Retrieval Status is required for Report #2.', 422);
        }
        $filters = [
            'search'              => $req->query('search'),
            'device_id'           => $req->query('device_id'),
            'boe'                 => $req->query('boe'),
            'vehicle_number'      => $req->query('vehicle_number'),
            'retrieval_status'    => $retrieval_status,
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'start_time'          => $req->query('start_time'),
            'end_time'            => $req->query('end_time'),
            'action_type'         => $req->query('action_type'),
            'allocation_point_id' => $req->query('allocation_point_id'),
            'sort_by'             => $req->query('sort_by', 'l.created_at'),
            'sort_dir'            => $req->query('sort_dir', 'DESC'),
            'page'                => (int) $req->query('page', 1),
            'per_page'            => 25,
        ];
        Response::success(DeviceRetrieval::reportAuditLog($filters));
    }

    /* ── Report #2 CSV export ───────────────────────────────────────────── */
    public function exportReport2(Request $req): void
    {
        $retrieval_status = $req->query('retrieval_status');
        if (empty($retrieval_status)) {
            Response::error('Retrieval Status is required for Report #2 export.', 422);
        }
        $filters = [
            'retrieval_status' => $retrieval_status,
            'search'           => $req->query('search'),
            'device_id'        => $req->query('device_id'),
            'boe'              => $req->query('boe'),
            'vehicle_number'   => $req->query('vehicle_number'),
            'from'             => $req->query('from'),
            'to'               => $req->query('to'),
            'action_type'      => $req->query('action_type'),
            'per_page'         => 5000,
            'page'             => 1,
        ];
        $result = DeviceRetrieval::reportAuditLog($filters);
        \App\Services\ExportService::streamCsv(
            $result['data'],
            'device-retrieval-report2-' . date('Ymd') . '.csv'
        );
    }

    /* ── New Device Retrieval — manual create ───────────────────────────── */
    public function createManual(Request $req): void
    {
        $data = $req->json();
        $required = ['device_id', 'boe', 'affixing_date', 'transaction_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::error("Field '{$field}' is required.", 422);
            }
        }
        $data['retrieval_status'] = 'NOT_RETRIEVED';
        $row = DeviceRetrieval::create($data);
        Response::success($row, 'Retrieval record created manually.', 201);
    }
}
