<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\OtherItem;

class OtherItemController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $filters = ['status' => $req->query('status')];
        $result  = OtherItem::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function store(Request $req): void
    {
        $data = $req->validated(['item_name' => 'required', 'quantity' => 'required']);
        $data = array_merge($data, $req->json());
        $row  = OtherItem::create($data);
        Response::success($row, 'Item created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        OtherItem::findOrFail($id);
        $row = OtherItem::update($id, $req->json());
        Response::success($row, 'Item updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        OtherItem::findOrFail($id);
        OtherItem::delete($id);
        Response::success(null, 'Item deleted');
    }

    public function bulkStatus(Request $req): void
    {
        $data   = $req->json();
        $ids    = $data['ids']    ?? [];
        $status = $data['status'] ?? '';
        if (empty($ids) || !$status) Response::error('ids and status are required');
        $count = OtherItem::bulkUpdateStatus($ids, $status);
        Response::success(['updated' => $count]);
    }
}
