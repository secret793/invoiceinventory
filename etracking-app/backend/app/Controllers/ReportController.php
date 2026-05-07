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
        $apId   = (int) $req->param('id');
        $search = trim($req->query('search', ''));
        $from   = $req->query('from', '');
        $to     = $req->query('to', '');
        $export = $req->query('export', '');

        $where  = ['cal.allocation_point_id = ?'];
        $params = [$apId];

        if ($from)   { $where[] = 'cal.affixing_date >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to)     { $where[] = 'cal.affixing_date <= ?'; $params[] = $to   . ' 23:59:59'; }
        if ($search) {
            $where[]  = '(d.device_id ILIKE ? OR cal.boe ILIKE ? OR cal.vehicle_number ILIKE ?)';
            $like     = "%{$search}%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $wStr = 'WHERE ' . implode(' AND ', $where);
        $rows = Database::query(
            "SELECT cal.*, d.device_id as device_identifier,
                    ap.name as allocation_point_name,
                    dest.name as destination_name,
                    r.name as route_name, lr.name as long_route_name
             FROM confirmed_affix_logs cal
             LEFT JOIN devices d          ON cal.device_id          = d.id
             LEFT JOIN allocation_points ap ON cal.allocation_point_id = ap.id
             LEFT JOIN destinations dest  ON cal.destination_id      = dest.id
             LEFT JOIN routes r           ON cal.route_id            = r.id
             LEFT JOIN long_routes lr     ON cal.long_route_id       = lr.id
             {$wStr}
             ORDER BY cal.affixing_date DESC",
            $params
        );

        if ($export === '1') {
            ExportService::streamCsv($rows, "dispatch-report-{$apId}-" . date('Ymd') . '.csv');
        } else {
            Response::success($rows);
        }
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
