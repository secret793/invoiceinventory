<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Receipt;

class ReceiptController
{
    /** Columns that exist in the receipts table (whitelist to block unknown fields) */
    private const ALLOWED = [
        'receipt_number', 'allocation_point_id', 'route_id', 'long_route_id',
        'sad_number', 'agent_name', 'agent_contact', 'amount', 'date', 'notes',
        'created_by', 'transaction_type', 'consignment_nature', 'moving_trucks',
        'used', 'billing_unit', 'base_unit_charge_usd', 'exchange_rate_used',
        'unit_charge_gmd', 'total_charge_gmd', 'destination_id',
        'consignee_details', 'shipper_details', 'description_of_goods',
    ];

    /** Nullable integer / bigint FK columns — empty string must become NULL */
    private const INT_NULLABLE = [
        'route_id', 'long_route_id', 'destination_id', 'allocation_point_id',
        'created_by', 'moving_trucks', 'used',
    ];

    private static function sanitize(array $data): array
    {
        // Rename agent_phone → agent_contact if frontend sends it
        if (isset($data['agent_phone']) && !isset($data['agent_contact'])) {
            $data['agent_contact'] = $data['agent_phone'];
        }

        // Keep only known columns
        $clean = array_intersect_key($data, array_flip(self::ALLOWED));

        // Convert empty strings to null for integer FK columns
        foreach (self::INT_NULLABLE as $col) {
            if (array_key_exists($col, $clean) && $clean[$col] === '') {
                $clean[$col] = null;
            }
        }

        return $clean;
    }

    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $filters = [
            'allocation_point_id' => $req->query('allocation_point_id'),
            'available'           => $req->query('available'),   // '1' → only used > 0
            'from'                => $req->query('from'),
            'to'                  => $req->query('to'),
            'search'              => $req->query('search'),
        ];
        $result = Receipt::listPaginated($page, $perPage, $filters);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function show(Request $req): void
    {
        Response::success(Receipt::findOrFail((int) $req->param('id')));
    }

    public function store(Request $req): void
    {
        $data               = self::sanitize($req->json());
        $data['created_by'] = $req->user()['id'];

        if (empty($data['receipt_number'])) {
            $data['receipt_number'] = 'REC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        if (empty($data['route_id']) && empty($data['long_route_id'])) {
            Response::error('At least one of route_id or long_route_id is required', 422);
        }

        $row = Receipt::create($data);
        Response::success($row, 'Receipt created', 201);
    }

    public function update(Request $req): void
    {
        $id = (int) $req->param('id');
        Receipt::findOrFail($id);
        $row = Receipt::update($id, self::sanitize($req->json()));
        Response::success($row, 'Receipt updated');
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        Receipt::findOrFail($id);
        Receipt::delete($id);
        Response::success(null, 'Receipt deleted');
    }

    public function pdf(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Receipt::findOrFail($id);
        Response::success($row, 'PDF export requires DomPDF. Returning JSON data.');
    }
}
