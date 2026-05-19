<?php

namespace App\Services;

use App\Core\Database;
use DateTime;

class OverstayCalculatorService
{
    const RATE_PER_DAY_GMD = 1000;
    const GRACE_SHORT      = 1;
    const GRACE_LONG       = 2;

    /**
     * CEILING/seconds-based overstay calculation (spec Engine B).
     * 1 second past a 24-hour cycle boundary = full extra day charged.
     * Overstay is frozen (not recalculated) once retrieval_status = RETRIEVED.
     */
    public static function calculate(array $retrieval): array
    {
        $gracePeriod = !empty($retrieval['long_route_id']) ? self::GRACE_LONG : self::GRACE_SHORT;

        // Overstay freezes once device is physically retrieved
        if (($retrieval['retrieval_status'] ?? '') === 'RETRIEVED') {
            $frozenDays   = (int)   ($retrieval['overstay_days']   ?? 0);
            $frozenAmount = (float) ($retrieval['overstay_amount'] ?? 0);
            return [
                'overstay_days'   => $frozenDays,
                'overstay_amount' => $frozenAmount,
                'overdue_hours'   => $frozenDays * 24,
                'total_days'      => $frozenDays + $gracePeriod,
                'grace_period'    => $gracePeriod,
                'rate_per_day'    => self::RATE_PER_DAY_GMD,
                'frozen'          => true,
            ];
        }

        $affixingDate       = new DateTime($retrieval['affixing_date'] ?? $retrieval['date'] ?? 'now');
        $now                = new DateTime();
        $gracePeriodSeconds = $gracePeriod * 86400;
        $totalSeconds       = $now->getTimestamp() - $affixingDate->getTimestamp();
        $totalDays          = (int) floor(max(0, $totalSeconds) / 86400);
        $overstaySeconds    = $totalSeconds - $gracePeriodSeconds;

        if ($overstaySeconds <= 0) {
            $overstayDays = 0;
        } else {
            // CEILING: 1 second into the next 24-hour cycle = full extra day
            $overstayDays = (int) ceil($overstaySeconds / 86400);
        }

        $overstayDays   = max(0, $overstayDays);
        $overstayAmount = $overstayDays * self::RATE_PER_DAY_GMD;

        return [
            'overstay_days'   => $overstayDays,
            'overstay_amount' => (float) $overstayAmount,
            'overdue_hours'   => $overstayDays * 24,
            'total_days'      => $totalDays,
            'grace_period'    => $gracePeriod,
            'rate_per_day'    => self::RATE_PER_DAY_GMD,
            'frozen'          => false,
        ];
    }

    /**
     * Recalculate and persist overstay for a single retrieval.
     * Skips silently if retrieval_status = RETRIEVED (overstay is frozen).
     */
    public static function recalculateForRetrieval(int $retrievalId): void
    {
        $row = Database::queryOne('SELECT * FROM device_retrievals WHERE id = ?', [$retrievalId]);
        if (!$row) return;
        if (($row['retrieval_status'] ?? '') === 'RETRIEVED') return;

        $calc = self::calculate($row);
        Database::execute(
            'UPDATE device_retrievals
             SET overstay_days = ?, overstay_amount = ?, overdue_hours = ?, updated_at = NOW()
             WHERE id = ?',
            [$calc['overstay_days'], $calc['overstay_amount'], $calc['overdue_hours'], $retrievalId]
        );
    }

    /**
     * Batch-recalculate overstay for ALL active (non-retrieved, non-archived) retrievals.
     *
     * Called by:
     *  - MonitoringController::index()    — every 10-second poll from the monitoring page
     *  - POST /api/overstay/recalculate  — on-demand via API (manual trigger / cron)
     *  - scripts/recalculate_overstay.php — Linux cron job
     *
     * Grace periods: short route = 1 day, long route = 2 days.
     * Rate: GMD 1,000 / day (flat).
     * Overstay is NOT recalculated for records with retrieval_status = RETRIEVED.
     *
     * @return int  Number of records updated.
     */
    public static function recalculateAll(): int
    {
        $rows = Database::query(
            "SELECT * FROM device_retrievals
             WHERE is_archived = FALSE
               AND retrieval_status != 'RETRIEVED'
               AND affixing_date IS NOT NULL",
            []
        );

        if (!$rows) return 0;

        $count = 0;
        foreach ($rows as $row) {
            $calc = self::calculate($row);

            // Only write if values have actually changed to reduce DB churn
            if (
                (int)   $row['overstay_days']   !== $calc['overstay_days']   ||
                (float) $row['overstay_amount']  !== $calc['overstay_amount'] ||
                (int)   ($row['overdue_hours'] ?? 0) !== $calc['overdue_hours']
            ) {
                Database::execute(
                    'UPDATE device_retrievals
                     SET overstay_days = ?, overstay_amount = ?, overdue_hours = ?, updated_at = NOW()
                     WHERE id = ?',
                    [$calc['overstay_days'], $calc['overstay_amount'], $calc['overdue_hours'], $row['id']]
                );
            }
            $count++;
        }
        return $count;
    }
}
