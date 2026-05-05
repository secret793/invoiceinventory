<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\DistributionPoint;

class DistributionPointController
{
    public function index(Request $req): void
    {
        Response::success(DistributionPoint::allWithCounts());
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = DistributionPoint::find($id);
        if (!$row) Response::notFound('Distribution point not found');
        Response::success($row);
    }

    public function devices(Request $req): void
    {
        $id      = (int) $req->param('id');
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = (int) $req->query('per_page', 25);
        $filters = ['status' => $req->query('status'), 'search' => $req->query('search')];

        $result = DistributionPoint::findWithDevices($id, $page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function store(Request $req): void
    {
        $data = $req->validated(['name' => 'required', 'location' => 'required']);
        $row  = DistributionPoint::create($data);
        Response::success($row, 'Distribution point created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        DistributionPoint::findOrFail($id);
        $row = DistributionPoint::update($id, $data);
        Response::success($row, 'Distribution point updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        DistributionPoint::findOrFail($id);
        DistributionPoint::delete($id);
        Response::success(null, 'Distribution point deleted');
    }
}
