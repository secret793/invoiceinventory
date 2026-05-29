<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Route;
use App\Models\AllocationPoint;
use App\Services\PermissionService;

class RouteController
{
    private function sanitize(array $data): array
    {
        foreach (['allocation_point_id', 'destination_id', 'allowed_days', 'base_usd_amount'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        if (array_key_exists('is_active', $data)) {
            $value = $data['is_active'];
            if (is_bool($value)) {
                $data['is_active'] = $value ? 1 : 0;
            } elseif (is_numeric($value)) {
                $data['is_active'] = ((int) $value) > 0 ? 1 : 0;
            } else {
                $v = strtolower((string) $value);
                $data['is_active'] = in_array($v, ['1', 'true', 'yes', 'on', 'active'], true) ? 1 : 0;
            }
        }
        return $data;
    }

    public function index(Request $req): void
    {
        $user = $req->user();
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(1000, max(1, (int) $req->query('per_page', 25)));
        $includeInactive = (string) $req->query('include_inactive', '0') === '1';
        $requestedApId = $req->query('allocation_point_id');

        $where = [];
        $params = [];

        if (!empty($requestedApId)) {
            $where[] = 'r.allocation_point_id = ?';
            $params[] = (int) $requestedApId;
        }

        if (!$includeInactive) {
            $where[] = 'COALESCE(r.is_active, 1) = 1';
        }

        if (!PermissionService::isSuperAdmin($user) && !PermissionService::hasRole($user, 'Warehouse Manager')) {
            $permittedApIds = AllocationPoint::getPermittedForUser($user);
            if (count($permittedApIds) === 0) {
                $permittedApIds = [-1];
            }
            $places = implode(',', array_fill(0, count($permittedApIds), '?'));
            $where[] = "r.allocation_point_id IN ($places)";
            $params = array_merge($params, $permittedApIds);
        }

        $result  = Route::paginate(
            $page,
            $perPage,
            implode(' AND ', $where),
            $params,
            'r.*, ap.name as allocation_point_name, d.name as destination_name',
            'r LEFT JOIN allocation_points ap ON r.allocation_point_id = ap.id LEFT JOIN destinations d ON r.destination_id = d.id',
            'r.name ASC'
        );
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }
    public function show(Request $req): void   { Response::success(Route::findOrFail((int) $req->param('id'))); }
    public function store(Request $req): void  {
        $data = $req->validated([
            'name' => 'required',
            'allocation_point_id' => 'required',
            'destination_id' => 'required',
        ]);
        $payload = $this->sanitize(array_merge($data, $req->json()));
        if (!array_key_exists('is_active', $payload)) {
            $payload['is_active'] = 1;
        }
        Response::success(Route::create($payload), 'Route created', 201);
    }
    public function update(Request $req): void {
        $id = (int) $req->param('id'); Route::findOrFail($id);
        $payload = $this->sanitize($req->json());
        Response::success(Route::update($id, $payload), 'Route updated');
    }
    public function destroy(Request $req): void {
        $id = (int) $req->param('id'); Route::findOrFail($id); Route::delete($id);
        Response::success(null, 'Route deleted');
    }
}
