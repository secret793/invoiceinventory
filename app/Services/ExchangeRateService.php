<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const API_URL = 'https://api.exchangerate-api.com/v4/latest/';
    private const CACHE_KEY = 'exchange_rate_gmd_usd';
    private const FALLBACK_RATE = 74.07;

    /**
     * Get GMD per 1 USD from cache or API
     * 
     * @return float GMD per USD (e.g., 74.07)
     */
    public function getGMDPerUSD(): float
    {
        // Check cache first
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            Log::debug('ExchangeRateService: Cache hit', ['rate' => $cached]);
            return (float) $cached;
        }

        // Fetch from API
        try {
            $rate = $this->fetchLiveRate();
            
            // Cache the rate for configured minutes
            $cacheMinutes = (int) config('currency.cache_minutes', 15);
            Cache::put(self::CACHE_KEY, $rate, now()->addMinutes($cacheMinutes));
            
            Log::info('ExchangeRateService: Rate fetched and cached', [
                'rate' => $rate,
                'cache_minutes' => $cacheMinutes
            ]);
            return $rate;
        } catch (\Exception $e) {
            Log::error('ExchangeRateService: API error', [
                'error' => $e->getMessage(),
                'using_fallback' => self::FALLBACK_RATE
            ]);
            
            // Use fallback rate
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
     * Fetch live exchange rate from ExchangeRate-API
     * 
     * API returns: { "base": "GMD", "rates": { "USD": 0.0135... } }
     * We need: GMD per USD = 1 / rates.USD
     * 
     * @return float GMD per USD
     * @throws \Exception
     */
    private function fetchLiveRate(): float
    {
        $apiUrl = config('currency.api_url', self::API_URL . 'GMD');
        
        $response = Http::timeout(10)->get($apiUrl);

        if (!$response->successful()) {
            throw new \Exception('ExchangeRate API returned status: ' . $response->status());
        }

        $data = $response->json();
        
        // Validate response structure
        if (!isset($data['rates']['USD']) || !is_numeric($data['rates']['USD'])) {
            throw new \Exception('Invalid exchange rate response format');
        }
        
        $usdPerGMD = $data['rates']['USD'];
        
        // Validate rate is positive
        if ($usdPerGMD <= 0) {
            throw new \Exception('Invalid exchange rate: ' . $usdPerGMD);
        }

        // Calculate GMD per USD: 1 / rates.USD
        $gmdPerUSD = 1 / $usdPerGMD;
        
        Log::debug('ExchangeRateService: API response parsed', [
            'usd_per_gmd' => $usdPerGMD,
            'gmd_per_usd' => $gmdPerUSD
        ]);

        return round($gmdPerUSD, 4);
    }

    /**
     * Clear exchange rate cache (manual refresh)
     * 
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Log::info('ExchangeRateService: Cache cleared manually');
    }

    /**
     * Get cached rate without fetching from API
     * 
     * @return float|null
     */
    public function getCachedRate(): ?float
    {
        $cached = Cache::get(self::CACHE_KEY);
        return $cached ? (float) $cached : null;
    }

    /**
     * Force refresh from API (clear cache and fetch)
     * 
     * @return float
     */
    public function refreshRate(): float
    {
        $this->clearCache();
        return $this->getGMDPerUSD();
    }

    /**
     * Get fallback rate (used when API unavailable)
     * 
     * @return float
     */
    public static function getFallbackRate(): float
    {
        return (float) config('currency.fallback_rate', self::FALLBACK_RATE);
    }

    /**
     * Check if cached rate exists and is still valid
     * 
     * @return bool
     */
    public function isCached(): bool
    {
        return Cache::has(self::CACHE_KEY);
    }
}
