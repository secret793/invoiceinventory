<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;

class InvoiceController
{
    public function index(Request $req): void
    {
        $status = $req->query('status', '');
        $from   = $req->query('from', '');
        $to     = $req->query('to', '');
        $search = trim($req->query('search', ''));
        $page   = max(1, (int) $req->query('page', 1));
        $limit  = min(100, max(1, (int) $req->query('per_page', 25)));
        $offset = ($page - 1) * $limit;

        $where  = ['1=1'];
        $params = [];

        if ($status) { $where[] = 'i.status = ?'; $params[] = $status; }
        if ($from)   { $where[] = 'i.reference_date >= ?'; $params[] = $from; }
        if ($to)     { $where[] = 'i.reference_date <= ?'; $params[] = $to; }
        if ($search) {
            $like = "%{$search}%";
            $where[] = '(i.boe ILIKE ? OR i.sad_number ILIKE ? OR i.vehicle_number ILIKE ? OR i.reference_number ILIKE ? OR i.consignee ILIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);

        $total = \App\Core\Database::queryOne(
            "SELECT COUNT(*) AS cnt FROM invoices i {$whereStr}", $params
        )['cnt'] ?? 0;

        $rows = \App\Core\Database::query(
            "SELECT i.*, d.device_id AS device_identifier, dr.vehicle_number AS dr_vehicle,
                    dr.overstay_days AS dr_overstay_days, dr.payment_status AS retrieval_payment_status
             FROM invoices i
             LEFT JOIN device_retrievals dr ON i.device_retrieval_id = dr.id
             LEFT JOIN devices d ON dr.device_id = d.id
             {$whereStr}
             ORDER BY i.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        \App\Core\Response::json([
            'success' => true,
            'data'    => $rows,
            'meta'    => [
                'total'       => (int) $total,
                'per_page'    => $limit,
                'current_page'=> $page,
                'last_page'   => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function show(Request $req): void
    {
        Response::success(Invoice::findOrFail((int) $req->param('id')));
    }

    public function generate(Request $req): void
    {
        // Delegated to DeviceRetrievalController::generateInvoice
        (new DeviceRetrievalController())->generateInvoice($req);
    }

    public function pdf(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Invoice::findOrFail($id);
        Response::success($row, 'PDF export requires DomPDF. Returning JSON data.');
    }
}
