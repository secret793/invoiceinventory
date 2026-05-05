<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\AllocationPoint;
use App\Models\Permission;
use App\Models\Device;

class AllocationPointController
{
    public function index(Request $req): void
    {
        $rows = AllocationPoint::allWithCounts();
        Response::success($rows);
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = AllocationPoint::findWithCounts($id);
        if (!$row) Response::notFound('Allocation point not found');
        Response::success($row);
    }

    public function devices(Request $req): void
    {
        $id      = (int) $req->param('id');
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = (int) $req->query('per_page', 25);

        AllocationPoint::findOrFail($id);

        $result = Device::paginate($page, $perPage, 'd.allocation_point_id = ?', [$id],
            'd.*', 'd', 'd.date_received DESC');
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function store(Request $req): void
    {
        $data = $req->validated(['name' => 'required', 'location' => 'required']);
        $row  = AllocationPoint::create($data);

        // Auto-generate permissions
        $slug = AllocationPoint::slugify($data['name']);
        Permission::createForAllocationPoint($slug);

        Response::success($row, 'Allocation point created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        AllocationPoint::findOrFail($id);
        $row = AllocationPoint::update($id, $data);
        Response::success($row, 'Allocation point updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        AllocationPoint::findOrFail($id);
        AllocationPoint::delete($id);
        Response::success(null, 'Allocation point deleted');
    }
}
