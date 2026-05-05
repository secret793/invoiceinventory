<?php

namespace App\Models;

use App\Core\Database;

class Invoice extends BaseModel
{
    protected static string $table = 'invoices';

    public static function findByRetrieval(int $retrievalId): ?array
    {
        return Database::queryOne('SELECT * FROM invoices WHERE device_retrieval_id = ? ORDER BY id DESC', [$retrievalId]);
    }
}
