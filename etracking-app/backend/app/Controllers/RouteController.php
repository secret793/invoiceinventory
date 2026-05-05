<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Route;

class RouteController
{
    public function index(Request $req): void  { Response::success(Route::allOrdered()); }
    public function show(Request $req): void   { Response::success(Route::findOrFail((int) $req->param('id'))); }
    public function store(Request $req): void  {
        $data = $req->validated(['name' => 'required']);
        Response::success(Route::create(array_merge($data, $req->json())), 'Route created', 201);
    }
    public function update(Request $req): void {
        $id = (int) $req->param('id'); Route::findOrFail($id);
        Response::success(Route::update($id, $req->json()), 'Route updated');
    }
    public function destroy(Request $req): void {
        $id = (int) $req->param('id'); Route::findOrFail($id); Route::delete($id);
        Response::success(null, 'Route deleted');
    }
}
