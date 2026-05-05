<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Destination;
use App\Models\Permission;

class DestinationController
{
    public function index(Request $req): void  { Response::success(Destination::allOrdered()); }
    public function show(Request $req): void   { Response::success(Destination::findOrFail((int) $req->param('id'))); }
    public function store(Request $req): void  {
        $data = $req->validated(['name' => 'required']);
        $data = array_merge($data, $req->json());
        $row  = Destination::create($data);
        // Auto-create permissions
        Permission::createForDestination(Destination::slugify($data['name']));
        Response::success($row, 'Destination created', 201);
    }
    public function update(Request $req): void {
        $id = (int) $req->param('id'); Destination::findOrFail($id);
        Response::success(Destination::update($id, $req->json()), 'Destination updated');
    }
    public function destroy(Request $req): void {
        $id = (int) $req->param('id'); Destination::findOrFail($id); Destination::delete($id);
        Response::success(null, 'Destination deleted');
    }
}
