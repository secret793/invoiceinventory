<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Regime;

class RegimeController
{
    public function index(Request $req): void  { Response::success(Regime::all('name', 'ASC')); }
    public function show(Request $req): void   { Response::success(Regime::findOrFail((int) $req->param('id'))); }
    public function store(Request $req): void  {
        $data = $req->validated(['name' => 'required']);
        Response::success(Regime::create(array_merge($data, $req->json())), 'Regime created', 201);
    }
    public function update(Request $req): void {
        $id = (int) $req->param('id'); Regime::findOrFail($id);
        Response::success(Regime::update($id, $req->json()), 'Regime updated');
    }
    public function destroy(Request $req): void {
        $id = (int) $req->param('id'); Regime::findOrFail($id); Regime::delete($id);
        Response::success(null, 'Regime deleted');
    }
}
