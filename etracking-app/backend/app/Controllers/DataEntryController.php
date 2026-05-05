<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\DataEntryAssignment;
use App\Models\ConfirmedAffixed;
use App\Models\AllocationPoint;
use App\Services\PermissionService;
use App\Services\NotificationService;

class DataEntryController
{
    public function index(Request $req): void
    {
        $user    = $req->user();
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));

        $filters = [
            'allocation_point_id' => $req->query('allocation_point_id'),
            'status'              => $req->query('status'),
        ];

        // Apply permission-based AP filtering
        $permittedIds = PermissionService::filterAllocationPointIds($user);
        if ($permittedIds !== null) {
            $filters['permitted_ap_ids'] = $permittedIds;
        }

        $result = DataEntryAssignment::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = DataEntryAssignment::findOrFail($id);

        // Attach allocation point
        $row['allocation_point'] = AllocationPoint::find((int) $row['allocation_point_id']);
        Response::success($row);
    }

    public function store(Request $req): void
    {
        $data = $req->validated(['allocation_point_id' => 'required']);
        $user = $req->user();
        $data['user_id'] = $user['id'];
        $data['status']  = 'PENDING';

        $row = DataEntryAssignment::create($data);
        NotificationService::created('Data Entry Assignment', (string) $row['id']);
        Response::success($row, 'Assignment created', 201);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        $data = $req->json();
        DataEntryAssignment::findOrFail($id);
        $row = DataEntryAssignment::update($id, $data);
        Response::success($row, 'Assignment updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        DataEntryAssignment::findOrFail($id);
        DataEntryAssignment::delete($id);
        Response::success(null, 'Assignment deleted');
    }

    public function assignToAgent(Request $req): void
    {
        $id         = (int) $req->param('id');
        $assignment = DataEntryAssignment::findOrFail($id);
        $user       = $req->user();

        // Check allocation point permission
        $ap     = AllocationPoint::find((int) $assignment['allocation_point_id']);
        $apSlug = $ap ? AllocationPoint::slugify($ap['name']) : '';

        if (!PermissionService::isSuperAdmin($user)
            && !PermissionService::hasRole($user, 'Warehouse Manager')
            && !PermissionService::hasPermission($user, "edit_allocationpoint_{$apSlug}")) {
            Response::forbidden("You do not have permission to assign to this allocation point.");
        }

        $data = $req->validated([
            'boe'            => 'required',
            'vehicle_number' => 'required',
            'regime'         => 'required',
            'destination'    => 'required',
        ]);

        $confirmed = ConfirmedAffixed::create(array_merge($data, [
            'device_id'           => $assignment['device_id'],
            'allocation_point_id' => $assignment['allocation_point_id'],
            'status'              => 'PENDING',
            'date'                => date('Y-m-d H:i:s'),
            'destination_id'      => $req->input('destination_id'),
            'route_id'            => $req->input('route_id'),
            'long_route_id'       => $req->input('long_route_id'),
            'manifest_date'       => $req->input('manifest_date'),
            'agency'              => $req->input('agency'),
            'agent_contact'       => $req->input('agent_contact'),
            'truck_number'        => $req->input('truck_number'),
            'driver_name'         => $req->input('driver_name'),
            'transaction_type'    => $req->input('transaction_type', 'SAD'),
            'sad_number'          => $data['boe'],
        ]));

        NotificationService::created('Confirmed Affixed', (string) $confirmed['id']);
        Response::success($confirmed, 'Device assigned to agent successfully', 201);
    }

    public function returnDevice(Request $req): void
    {
        $id         = (int) $req->param('id');
        $assignment = DataEntryAssignment::findOrFail($id);
        $note       = $req->input('return_note', '');

        if (!$note) Response::error('return_note is required');

        DataEntryAssignment::update($id, [
            'status'      => 'RETURNED',
            'return_note' => $note,
        ]);

        // Update related monitoring and retrieval notes
        Database::execute(
            'UPDATE monitorings SET note = ?, updated_at = NOW() WHERE device_id = ?',
            [$note, $assignment['device_id']]
        );
        Database::execute(
            'UPDATE device_retrievals SET note = ?, updated_at = NOW() WHERE device_id = ?',
            [$note, $assignment['device_id']]
        );

        Response::success(null, 'Device returned successfully');
    }

    public function dispatchLogs(Request $req): void
    {
        $id   = (int) $req->param('id');
        $from = $req->query('from');
        $to   = $req->query('to');

        $where  = ['ca.allocation_point_id = (SELECT allocation_point_id FROM data_entry_assignments WHERE id = ?)'];
        $params = [$id];

        if ($from) { $where[] = 'ca.date >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to)   { $where[] = 'ca.date <= ?'; $params[] = $to   . ' 23:59:59'; }

        $rows = Database::query(
            'SELECT ca.*, d.device_id as device_identifier
             FROM confirmed_affix_logs ca
             LEFT JOIN devices d ON ca.device_id = d.id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY ca.created_at DESC',
            $params
        );

        Response::success($rows);
    }

    public function receipts(Request $req): void
    {
        $id         = (int) $req->param('id');
        $assignment = DataEntryAssignment::findOrFail($id);

        $rows = Database::query(
            'SELECT r.* FROM receipts r
             JOIN confirmed_affixeds ca ON r.id = ca.receipt_id
             WHERE ca.allocation_point_id = ?
             ORDER BY r.date DESC',
            [$assignment['allocation_point_id']]
        );

        Response::success($rows);
    }
}
