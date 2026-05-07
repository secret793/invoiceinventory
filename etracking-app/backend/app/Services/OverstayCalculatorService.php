<?php

namespace App\Services;

use App\Core\Database;
use DateTime;

class OverstayCalculatorService
{
    const RATE_PER_DAY_GMD = 1000;
    const GRACE_SHORT      = 1;
    const GRACE_LONG       = 2;

    public static function calculate(array $retrieval): array
    {
        $affixingDate  = new DateTime($retrieval['affixing_date'] ?? $retrieval['date'] ?? 'now');
        $now           = new DateTime();
        $diff          = $now->diff($affixingDate);
        $totalDays     = (int) $diff->days;

        $gracePeriod    = !empty($retrieval['long_route_id']) ? self::GRACE_LONG : self::GRACE_SHORT;
        $overstayDays   = max(0, $totalDays - $gracePeriod);
        $overstayAmount = $overstayDays * self::RATE_PER_DAY_GMD;

        return [
            'overstay_days'   => $overstayDays,
            'overstay_amount' => (float) $overstayAmount,
            'overdue_hours'   => $overstayDays * 24,
            'total_days'      => $totalDays,
            'grace_period'    => $gracePeriod,
            'rate_per_day'    => self::RATE_PER_DAY_GMD,
        ];
    }

    public static function recalculateForRetrieval(int $retrievalId): void
    {
        $row = Database::queryOne('SELECT * FROM device_retrievals WHERE id = ?', [$retrievalId]);
        if (!$row) return;

        $calc = self::calculate($row);
        Database::execute(
            'UPDATE device_retrievals SET overstay_days = ?, overstay_amount = ?, overdue_hours = ?, updated_at = NOW() WHERE id = ?',
            [$calc['overstay_days'], $calc['overstay_amount'], $calc['overdue_hours'], $retrievalId]
        );
    }
}
