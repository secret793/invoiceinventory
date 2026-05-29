<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Destination;
use App\Models\Permission;

class DestinationController
{
    /** Coerce types so PostgreSQL binding works correctly */
    private function sanitize(array $data): array
    {
        // Empty strings → NULL for numeric / FK columns
        foreach (['regime_id', 'latitude', 'longitude'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
        // Coerce boolean to integer — works for both MySQL TINYINT(1) and PostgreSQL BOOLEAN
        if (array_key_exists('is_default_location', $data)) {
            $data['is_default_location'] = filter_var($data['is_default_location'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        return $data;
    }

    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(1000, max(1, (int) $req->query('per_page', 25)));
        $result  = Destination::paginate(
            $page,
            $perPage,
            '',
            [],
            'destinations.*, r.name as regime_name',
            'LEFT JOIN regimes r ON destinations.regime_id = r.id',
            'destinations.name ASC'
        );
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }
    public function show(Request $req): void   { Response::success(Destination::findOrFail((int) $req->param('id'))); }

    public function store(Request $req): void  {
        $data = $req->validated(['name' => 'required']);
        $data = $this->sanitize(array_merge($data, $req->json()));
        $row  = Destination::create($data);
        Permission::createForDestination(Destination::slugify($data['name']));
        Response::success($row, 'Destination created', 201);
    }

    public function update(Request $req): void {
        $id = (int) $req->param('id');
        Destination::findOrFail($id);
        Response::success(Destination::update($id, $this->sanitize($req->json())), 'Destination updated');
    }

    public function destroy(Request $req): void {
        $id = (int) $req->param('id');
        Destination::findOrFail($id);
        Destination::delete($id);
        Response::success(null, 'Destination deleted');
    }
}
