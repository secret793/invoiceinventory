<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
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
        ];

        $result = Monitoring::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }
}
