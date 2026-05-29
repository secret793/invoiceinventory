<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Company;

class CompanyController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(1000, max(1, (int) $req->query('per_page', 25)));
        $result  = Company::paginate($page, $perPage, '', [], '*', '', 'name ASC');
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function store(Request $req): void
    {
        $data = $req->json();
        if (empty(trim($data['name'] ?? ''))) Response::error('Name is required', 422);
        $data['status'] = $data['status'] ?? 'Active';
        $company = Company::create(['name' => $data['name'], 'status' => $data['status']]);
        Response::success($company, 'Company created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        Company::findOrFail($id);
        unset($data['id'], $data['created_at']);
        $company = Company::update($id, ['name' => $data['name'] ?? null, 'status' => $data['status'] ?? 'Active']);
        Response::success($company, 'Company updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        Company::findOrFail($id);
        Company::delete($id);
        Response::success(null, 'Company deleted');
    }
}
