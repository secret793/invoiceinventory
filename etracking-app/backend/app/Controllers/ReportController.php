<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\ExportService;

class ReportController
{
    private function dateParams(Request $req): array
    {
        $params = [];
        $where  = [];
        if ($req->query('from')) { $where[] = 'date >= ?'; $params[] = $req->query('from') . ' 00:00:00'; }
        if ($req->query('to'))   { $where[] = 'date <= ?'; $params[] = $req->query('to')   . ' 23:59:59'; }
        return [$where, $params];
    }

    public function dispatchReport(Request $req): void
    {
        $id   = (int) $req->param('id');
        $rows = Database::query(
            'SELECT cal.*, d.device_id as device_identifier
             FROM confirmed_affix_logs cal
             LEFT JOIN devices d ON cal.device_id = d.id
             WHERE cal.confirmed_affixed_id = ? OR
                   cal.allocation_point_id = (SELECT allocation_point_id FROM data_entry_assignments WHERE id = ?)
             ORDER BY cal.created_at DESC',
            [$id, $id]
        );
        ExportService::streamCsv($rows, "dispatch-report-{$id}-" . date('Ymd') . '.csv');
    }

    public function confirmedAffixReport(Request $req): void
    {
        [$where, $params] = $this->dateParams($req);
        $wStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $rows = Database::query("SELECT * FROM confirmed_affixeds $wStr ORDER BY date DESC", $params);
        ExportService::streamCsv($rows, 'confirmed-affix-report-' . date('Ymd') . '.csv');
    }

    public function deviceRetrievalReport(Request $req): void
    {
        [$where, $params] = $this->dateParams($req);
        $wStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $rows = Database::query("SELECT * FROM device_retrievals $wStr ORDER BY date DESC", $params);
        ExportService::streamCsv($rows, 'device-retrieval-report-' . date('Ymd') . '.csv');
    }

    public function deviceRetrievalReport2(Request $req): void
    {
        [$where, $params] = $this->dateParams($req);
        $wStr = $where ? 'WHERE dr.' . implode(' AND dr.', $where) : '';
        $rows = Database::query(
            "SELECT dr.*, d.device_id as device_identifier, ap.name as allocation_point_name
             FROM device_retrievals dr
             LEFT JOIN devices d ON dr.device_id = d.id
             LEFT JOIN allocation_points ap ON dr.allocation_point_id = ap.id
             $wStr ORDER BY dr.date DESC",
            $params
        );
        ExportService::streamCsv($rows, 'device-retrieval-report-2-' . date('Ymd') . '.csv');
    }

    public function receipts(Request $req): void
    {
        [$where, $params] = $this->dateParams($req);
        $wStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $rows = Database::query("SELECT * FROM receipts $wStr ORDER BY date DESC", $params);
        ExportService::streamCsv($rows, 'receipts-' . date('Ymd') . '.csv');
    }

    public function generatedReceipts(Request $req): void
    {
        $rows = Database::query("SELECT * FROM receipts WHERE receipt_number IS NOT NULL ORDER BY date DESC");
        ExportService::streamCsv($rows, 'generated-receipts-' . date('Ymd') . '.csv');
    }

    public function dispatchFinanceRecords(Request $req): void
    {
        [$where, $params] = $this->dateParams($req);
        $base  = array_merge(['dr.finance_approval_date IS NOT NULL'], $where);
        $wStr  = 'WHERE dr.' . implode(' AND dr.', $base);
        $rows  = Database::query(
            "SELECT dr.*, d.device_id as device_identifier
             FROM device_retrievals dr
             LEFT JOIN devices d ON dr.device_id = d.id
             $wStr ORDER BY dr.finance_approval_date DESC",
            $params
        );
        ExportService::streamCsv($rows, 'dispatch-finance-records-' . date('Ymd') . '.csv');
    }

    public function overstayReceipts(Request $req): void
    {
        $rows = Database::query(
            "SELECT r.* FROM receipts r
             JOIN device_retrievals dr ON r.id = dr.receipt_id
             WHERE dr.overstay_days > 0
             ORDER BY r.date DESC"
        );
        ExportService::streamCsv($rows, 'overstay-receipts-' . date('Ymd') . '.csv');
    }

    public function overstayInvoices(Request $req): void
    {
        $rows = Database::query(
            "SELECT i.*, dr.overstay_days, dr.vehicle_number, d.device_id as device_identifier
             FROM invoices i
             JOIN device_retrievals dr ON i.device_retrieval_id = dr.id
             LEFT JOIN devices d ON dr.device_id = d.id
             WHERE dr.overstay_days > 0
             ORDER BY i.created_at DESC"
        );
        ExportService::streamCsv($rows, 'overstay-invoices-' . date('Ymd') . '.csv');
    }

    public function overstayDevices(Request $req): void
    {
        $rows = Database::query(
            "SELECT dr.*, d.device_id as device_identifier, ap.name as allocation_point_name
             FROM device_retrievals dr
             LEFT JOIN devices d ON dr.device_id = d.id
             LEFT JOIN allocation_points ap ON dr.allocation_point_id = ap.id
             WHERE dr.overstay_days > 0
             ORDER BY dr.overstay_days DESC"
        );
        ExportService::streamCsv($rows, 'overstay-devices-' . date('Ymd') . '.csv');
    }

    public function overstayDevicesPdf(Request $req): void
    {
        // PDF not implemented without DomPDF dependency — return JSON fallback
        $rows = Database::query("SELECT * FROM device_retrievals WHERE overstay_days > 0");
        Response::success($rows, 'PDF export requires DomPDF. Returning JSON data.');
    }
}
