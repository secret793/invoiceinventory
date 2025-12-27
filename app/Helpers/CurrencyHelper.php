<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Format GMD amount with symbol
     * 
     * @param float $amount Amount in GMD
     * @param bool $includeSymbol Whether to include symbol
     * @return string Formatted GMD amount (e.g., "D 1,234.56")
     */
    public static function formatGMD(float $amount, bool $includeSymbol = true): string
    {
        $formatted = number_format($amount, 2, '.', ',');
        $symbol = config('currency.formatting.gmd_symbol', 'D');
        return $includeSymbol ? "{$symbol} {$formatted}" : $formatted;
    }

    /**
     * Format USD amount with symbol
     * 
     * @param float $amount Amount in USD
     * @param bool $includeSymbol Whether to include symbol
     * @return string Formatted USD amount (e.g., "$ 1,234.56")
     */
    public static function formatUSD(float $amount, bool $includeSymbol = true): string
    {
        $formatted = number_format($amount, 2, '.', ',');
        $symbol = config('currency.formatting.usd_symbol', '$');
        return $includeSymbol ? "{$symbol} {$formatted}" : $formatted;
    }

    /**
     * Convert GMD to USD
     * 
     * @param float $gmd Amount in GMD
     * @param float|null $rate Optional fixed rate override
     * @return float Amount in USD
     */
    public static function gmdToUsd(float $gmd, ?float $rate = null): float
    {
        $rate = $rate ?? (float) config('currency.fallback_rate', 74.07);
        return round($gmd / $rate, 2);
    }

    /**
     * Convert USD to GMD
     * 
     * @param float $usd Amount in USD
     * @param float|null $rate Optional fixed rate override
     * @return float Amount in GMD
     */
    public static function usdToGmd(float $usd, ?float $rate = null): float
    {
        $rate = $rate ?? (float) config('currency.fallback_rate', 74.07);
        return round($usd * $rate, 2);
    }

    /**
     * Get the current exchange rate (GMD per USD)
     * Uses ExchangeRateService
     * 
     * @return float Current rate
     */
    public static function getCurrentRate(): float
    {
        return app(\App\Services\ExchangeRateService::class)->getGMDPerUSD();
    }

    /**
     * Format amount with currency based on type
     * 
     * @param float $amount Amount to format
     * @param string $currency 'GMD' or 'USD'
     * @param bool $includeSymbol Whether to include symbol
     * @return string Formatted amount
     */
    public static function format(float $amount, string $currency = 'GMD', bool $includeSymbol = true): string
    {
        return $currency === 'USD' 
            ? self::formatUSD($amount, $includeSymbol)
            : self::formatGMD($amount, $includeSymbol);
    }

    /**
     * Convert amount from one currency to another
     * 
     * @param float $amount Amount to convert
     * @param string $from Source currency ('GMD' or 'USD')
     * @param string $to Target currency ('GMD' or 'USD')
     * @param float|null $rate Optional fixed rate
     * @return float Converted amount
     */
    public static function convert(float $amount, string $from = 'USD', string $to = 'GMD', ?float $rate = null): float
    {
        if ($from === $to) {
            return $amount;
        }

        if ($from === 'USD' && $to === 'GMD') {
            return self::usdToGmd($amount, $rate);
        } elseif ($from === 'GMD' && $to === 'USD') {
            return self::gmdToUsd($amount, $rate);
        }

        throw new \InvalidArgumentException("Invalid currency conversion: {$from} to {$to}");
    }

    /**
     * Get rate status badge color based on cache state
     * 
     * @return string 'success' (cached) or 'warning' (using fallback)
     */
    public static function getRateBadgeColor(): string
    {
        $service = app(\App\Services\ExchangeRateService::class);
        return $service->isCached() ? 'success' : 'warning';
    }

    /**
     * Get rate status message
     * 
     * @return string Status message
     */
    public static function getRateStatus(): string
    {
        $service = app(\App\Services\ExchangeRateService::class);
        $rate = $service->getCachedRate() ?? config('currency.fallback_rate', 74.07);
        
        if ($service->isCached()) {
            return "Live rate: {$rate}";
        }
        return "Fallback rate: {$rate}";
    }
}
