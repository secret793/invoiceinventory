<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Monitoring;

class MonitoringController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));

        $filters = [
            'overstay_min'     => $req->query('overstay_min'),
            'overstay_max'     => $req->query('overstay_max'),
            'retrieval_status' => $req->query('retrieval_status'),
            'search'           => $req->query('search'),
            'overdue'          => $req->query('overdue'),
            'pending'          => $req->query('pending'),
        ];

        $result = Monitoring::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function addNote(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();

        $note         = trim($data['note'] ?? '');
        $manifestDate = $data['manifest_date'] ?? null;

        if (!$note) Response::error('note is required');

        $row = Monitoring::find($id);
        if (!$row) Response::notFound('Monitoring record not found');

        $update = ['note' => $note, 'updated_at' => date('Y-m-d H:i:s')];
        if ($manifestDate) $update['manifest_date'] = $manifestDate;

        Database::execute(
            'UPDATE monitorings SET note = ?, manifest_date = COALESCE(?, manifest_date), updated_at = NOW() WHERE id = ?',
            [$note, $manifestDate ?: null, $id]
        );

        Response::success(Monitoring::find($id), 'Note added successfully');
    }
}
