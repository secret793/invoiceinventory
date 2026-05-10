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

        // ── Branch 1: confirmed_affixeds (pending dispatches not yet affixed) ──
        $w1 = ['ca.allocation_point_id = ?'];
        $p1 = [$apId];
        if ($from)   { $w1[] = 'ca.date >= ?'; $p1[] = $from . ' 00:00:00'; }
        if ($to)     { $w1[] = 'ca.date <= ?'; $p1[] = $to   . ' 23:59:59'; }
        if ($search) {
            $like  = "%{$search}%";
            $w1[]  = '(d1.device_id ILIKE ? OR ca.boe ILIKE ? OR ca.vehicle_number ILIKE ?)';
            $p1[]  = $like; $p1[] = $like; $p1[] = $like;
        }
        $ws1 = 'WHERE ' . implode(' AND ', $w1);

        // ── Branch 2: confirmed_affix_logs (historical / affixed records) ──────
        $w2 = ['cal.allocation_point_id = ?'];
        $p2 = [$apId];
        if ($from)   { $w2[] = 'cal.affixing_date >= ?'; $p2[] = $from . ' 00:00:00'; }
        if ($to)     { $w2[] = 'cal.affixing_date <= ?'; $p2[] = $to   . ' 23:59:59'; }
        if ($search) {
            $like  = "%{$search}%";
            $w2[]  = '(d2.device_id ILIKE ? OR cal.boe ILIKE ? OR cal.vehicle_number ILIKE ?)';
            $p2[]  = $like; $p2[] = $like; $p2[] = $like;
        }
        $ws2 = 'WHERE ' . implode(' AND ', $w2);

        $rows = Database::query(
            "SELECT
                ca.id, ca.device_id, ca.boe, ca.sad_number, ca.transaction_type,
                ca.vehicle_number, ca.truck_number, ca.driver_name,
                ca.regime, ca.destination, ca.destination_id,
                ca.route_id, ca.long_route_id, ca.manifest_date,
                ca.agency, ca.agent_contact, ca.receipt_id,
                ca.date         AS dispatch_date,
                ca.date         AS affixing_date,
                ca.status,
                ca.allocation_point_id,
                d1.device_id    AS device_identifier,
                ap1.name        AS allocation_point_name,
                dest1.name      AS destination_name,
                r1.name         AS route_name,
                lr1.name        AS long_route_name
             FROM confirmed_affixeds ca
             LEFT JOIN devices d1            ON ca.device_id          = d1.id
             LEFT JOIN allocation_points ap1 ON ca.allocation_point_id = ap1.id
             LEFT JOIN destinations dest1    ON ca.destination_id      = dest1.id
             LEFT JOIN routes r1             ON ca.route_id            = r1.id
             LEFT JOIN long_routes lr1       ON ca.long_route_id       = lr1.id
             {$ws1}

             UNION ALL

             SELECT
                cal.id, cal.device_id, cal.boe, NULL AS sad_number, NULL AS transaction_type,
                cal.vehicle_number, NULL AS truck_number, NULL AS driver_name,
                NULL AS regime, NULL AS destination, cal.destination_id,
                cal.route_id, cal.long_route_id, NULL AS manifest_date,
                NULL AS agency, NULL AS agent_contact, NULL AS receipt_id,
                cal.affixing_date AS dispatch_date,
                cal.affixing_date AS affixing_date,
                cal.status,
                cal.allocation_point_id,
                d2.device_id    AS device_identifier,
                ap2.name        AS allocation_point_name,
                dest2.name      AS destination_name,
                r2.name         AS route_name,
                lr2.name        AS long_route_name
             FROM confirmed_affix_logs cal
             LEFT JOIN devices d2            ON cal.device_id          = d2.id
             LEFT JOIN allocation_points ap2 ON cal.allocation_point_id = ap2.id
             LEFT JOIN destinations dest2    ON cal.destination_id      = dest2.id
             LEFT JOIN routes r2             ON cal.route_id            = r2.id
             LEFT JOIN long_routes lr2       ON cal.long_route_id       = lr2.id
             {$ws2}

             ORDER BY dispatch_date DESC",
            array_merge($p1, $p2)
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
