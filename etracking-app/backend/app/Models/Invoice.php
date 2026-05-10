<?php

namespace App\Models;

use App\Core\Database;

class Invoice extends BaseModel
{
    protected static string $table = 'invoices';

    public static function findByRetrieval(int $retrievalId): ?array
    {
        return Database::queryOne(
            'SELECT * FROM invoices WHERE device_retrieval_id = ? ORDER BY id DESC LIMIT 1',
            [$retrievalId]
        );
    }

    public static function generateReference(): string
    {
        return 'OVR-' . date('YmdHis');
    }

    public static function buildFromRetrieval(array $retrieval, array $calc): array
    {
        // Per spec: invoice status is 'PD' (paid) immediately on generation —
        // the cash was collected at the customs post before the device is released.
        return [
            'device_retrieval_id' => (int) $retrieval['id'],
            'device_id'           => (int) ($retrieval['device_id'] ?? 0),
            'reference_number'    => self::generateReference(),
            'reference_date'      => date('Y-m-d'),
            'boe'                 => $retrieval['boe']          ?? '',
            'sad_number'          => $retrieval['sad_number']   ?? '',
            'vehicle_number'      => $retrieval['vehicle_number'] ?? '',
            'regime'              => $retrieval['regime']        ?? '',
            'consignee'           => $retrieval['consignee']    ?? $retrieval['agency'] ?? '',
            'agent'               => $retrieval['agency']       ?? '',
            'customs_post'        => $retrieval['destination']  ?? '',
            'overstay_days'       => $calc['overstay_days'],
            'overstay_amount'     => $calc['overstay_amount'],
            'penalty_amount'      => $calc['overstay_amount'],
            'total_amount'        => $calc['overstay_amount'],
            'exchange_rate'       => 1,
            'status'              => 'PD',
            'notes'               => null,
        ];
    }
}
