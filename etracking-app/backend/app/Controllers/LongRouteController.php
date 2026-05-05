<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\LongRoute;

class LongRouteController
{
    public function index(Request $req): void  { Response::success(LongRoute::allOrdered()); }
    public function show(Request $req): void   { Response::success(LongRoute::findOrFail((int) $req->param('id'))); }
    public function store(Request $req): void  {
        $data = $req->validated(['name' => 'required']);
        Response::success(LongRoute::create(array_merge($data, $req->json())), 'Long route created', 201);
    }
    public function update(Request $req): void {
        $id = (int) $req->param('id'); LongRoute::findOrFail($id);
        Response::success(LongRoute::update($id, $req->json()), 'Long route updated');
    }
    public function destroy(Request $req): void {
        $id = (int) $req->param('id'); LongRoute::findOrFail($id); LongRoute::delete($id);
        Response::success(null, 'Long route deleted');
    }
}
