<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const FALLBACK_RATE = 74.07;

    /**
     * Get GMD per 1 USD from database settings
     * 
     * @return float GMD per USD (e.g., 74.07)
     */
    public function getGMDPerUSD(): float
    {
        try {
            $rate = SystemSetting::getExchangeRate();
            
            Log::debug('ExchangeRateService: Rate fetched from database', [
                'rate' => $rate
            ]);
            
            return $rate;
        } catch (\Exception $e) {
            Log::error('ExchangeRateService: Database error', [
                'error' => $e->getMessage(),
                'using_fallback' => self::FALLBACK_RATE
            ]);
            
            return self::FALLBACK_RATE;
        }
    }

    /**
     * Convert USD amount to GMD
     * 
     * @param float $usd USD amount
     * @param float|null $rate Optional fixed rate (for testing/override)
     * @return float GMD amount
     */
    public function convertUSDToGMD(float $usd, ?float $rate = null): float
    {
        $rate = $rate ?? $this->getGMDPerUSD();
        $gmd = $usd * $rate;
        
        Log::debug('ExchangeRateService: USD to GMD conversion', [
            'usd' => $usd,
            'rate' => $rate,
            'gmd' => $gmd
        ]);
        
        return round($gmd, 2);
    }

    /**
     * Get fallback rate (used when database unavailable)
     * 
     * @return float
     */
    public static function getFallbackRate(): float
    {
        return self::FALLBACK_RATE;
    }
}
