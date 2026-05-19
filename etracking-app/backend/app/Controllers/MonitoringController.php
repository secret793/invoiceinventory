<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Monitoring;
use App\Services\OverstayCalculatorService;

class MonitoringController
{
    public function index(Request $req): void
    {
        // Auto-recalculate overstay on every monitoring poll (10 s interval).
        // Skips RETRIEVED/archived records automatically — only active retrievals
        // are updated. Write-only-if-changed logic in recalculateAll() keeps DB
        // load minimal even at the 10-second polling cadence.
        OverstayCalculatorService::recalculateAll();

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

    /**
     * POST /api/overstay/recalculate
     * On-demand batch recalculation — callable from the UI or an external cron.
     * Requires Super Admin or Warehouse Manager role (enforced in routes/api.php).
     */
    public function recalculate(Request $req): void
    {
        $updated = OverstayCalculatorService::recalculateAll();
        Response::success(
            ['updated' => $updated],
            "Overstay recalculated for {$updated} active retrieval(s)."
        );
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
