<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Receipt;

class ReceiptController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $filters = [
            'allocation_point_id' => $req->query('allocation_point_id'),
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'search'              => $req->query('search'),
        ];
        $result = Receipt::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        Response::success(Receipt::findOrFail((int) $req->param('id')));
    }

    public function store(Request $req): void
    {
        $data = $req->json();
        $user = $req->user();
        $data['created_by'] = $user['id'];
        if (empty($data['receipt_number'])) {
            $data['receipt_number'] = 'REC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        $row = Receipt::create($data);
        Response::success($row, 'Receipt created', 201);
    }

    public function update(Request $req): void
    {
        $id = (int) $req->param('id');
        Receipt::findOrFail($id);
        $row = Receipt::update($id, $req->json());
        Response::success($row, 'Receipt updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        Receipt::findOrFail($id);
        Receipt::delete($id);
        Response::success(null, 'Receipt deleted');
    }

    public function pdf(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Receipt::findOrFail($id);
        Response::success($row, 'PDF export requires DomPDF. Returning JSON data.');
    }
}
