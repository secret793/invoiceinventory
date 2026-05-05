<?php

namespace App\Services;

use App\Models\Route;
use App\Models\LongRoute;
use App\Models\SystemSetting;
use DateTime;

class OverstayCalculatorService
{
    public static function calculate(array $retrieval): array
    {
        $affixingDate = new DateTime($retrieval['affixing_date'] ?? $retrieval['date'] ?? 'now');
        $now          = new DateTime();
        $diff         = $now->diff($affixingDate);
        $totalDays    = $diff->days;

        // Get route to determine allowed days
        $allowedDays = 1;
        $baseUSD     = 0;

        if (!empty($retrieval['route_id'])) {
            $route = Route::find((int) $retrieval['route_id']);
            if ($route) {
                $allowedDays = (int) ($route['allowed_days'] ?? 1);
                $baseUSD     = (float) ($route['base_usd_amount'] ?? $route['amount'] ?? 0);
            }
        } elseif (!empty($retrieval['long_route_id'])) {
            $route = LongRoute::find((int) $retrieval['long_route_id']);
            if ($route) {
                $allowedDays = (int) ($route['allowed_days'] ?? 3);
                $baseUSD     = (float) ($route['base_usd_amount'] ?? $route['amount'] ?? 0);
            }
        }

        $overstayDays   = max(0, $totalDays - $allowedDays);
        $exchangeRate   = (float) SystemSetting::getValue('exchange_rate_gmd_usd', 60.0);
        $overstayAmount = round($overstayDays * $baseUSD * $exchangeRate, 2);

        return [
            'overstay_days'   => $overstayDays,
            'overstay_amount' => $overstayAmount,
            'overdue_hours'   => $overstayDays * 24,
            'total_days'      => $totalDays,
            'allowed_days'    => $allowedDays,
            'exchange_rate'   => $exchangeRate,
        ];
    }

    public static function recalculateForRetrieval(int $retrievalId): void
    {
        $row = \App\Core\Database::queryOne('SELECT * FROM device_retrievals WHERE id = ?', [$retrievalId]);
        if (!$row) return;

        $calc = self::calculate($row);
        \App\Core\Database::execute(
            'UPDATE device_retrievals SET overstay_days = ?, overstay_amount = ?, overdue_hours = ?, updated_at = NOW() WHERE id = ?',
            [$calc['overstay_days'], $calc['overstay_amount'], $calc['overdue_hours'], $retrievalId]
        );
    }
}
