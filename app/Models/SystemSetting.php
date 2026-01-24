<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a setting value by key
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string|null $description Optional description
     * @return SystemSetting
     */
    public static function setSetting(string $key, $value, ?string $description = null): SystemSetting
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );
    }

    /**
     * Get exchange rate (GMD per USD)
     * 
     * @return float
     */
    public static function getExchangeRate(): float
    {
        return (float) static::getSetting('exchange_rate_gmd_usd', 74.07);
    }

    /**
     * Set exchange rate (GMD per USD)
     * 
     * @param float $rate
     * @return SystemSetting
     */
    public static function setExchangeRate(float $rate): SystemSetting
    {
        return static::setSetting(
            'exchange_rate_gmd_usd',
            $rate,
            'Exchange Rate: GMD per 1 USD'
        );
    }
}
