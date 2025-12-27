<?php

namespace App\Models;
use App\Models\DistributionPoint;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DistributionPoint extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location'];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function otherItems()
    {
        return $this->hasMany(OtherItem::class);
    }

    /**
     * Get count of devices with RECEIVED status
     *
     * @return int
     */
    public function getReceivedDevicesCount(): int
    {
        try {
            return $this->devices()->where('status', 'RECEIVED')->count();
        } catch (\Exception $e) {
            Log::error('Error counting received devices: ' . $e->getMessage(), [
                'distribution_point_id' => $this->id,
                'exception' => $e
            ]);
            return 0;
        }
    }

    /**
     * Get count of devices with statuses other than RECEIVED
     *
     * @return int
     */
    public function getOtherStatusDevicesCount(): int
    {
        try {
            return $this->devices()->where('status', '!=', 'RECEIVED')->count();
        } catch (\Exception $e) {
            Log::error('Error counting other status devices: ' . $e->getMessage(), [
                'distribution_point_id' => $this->id,
                'exception' => $e
            ]);
            return 0;
        }
    }

    /**
     * Get cached received devices count with automatic cache invalidation
     *
     * @return int
     */
    public static function getCachedReceivedCount(int $distributionPointId): int
    {
        $cacheKey = "distribution_point_{$distributionPointId}_received_count";

        try {
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($distributionPointId) {
                $point = self::find($distributionPointId);
                return $point ? $point->getReceivedDevicesCount() : 0;
            });
        } catch (\Exception $e) {
            Log::error('Cache error for received count: ' . $e->getMessage(), [
                'distribution_point_id' => $distributionPointId
            ]);

            // Fallback to direct query on cache failure
            try {
                $point = self::find($distributionPointId);
                return $point ? $point->getReceivedDevicesCount() : 0;
            } catch (\Exception $innerException) {
                Log::error('Fallback query failed: ' . $innerException->getMessage());
                return 0;
            }
        }
    }

    /**
     * Get cached other status devices count with automatic cache invalidation
     *
     * @return int
     */
    public static function getCachedOtherStatusCount(int $distributionPointId): int
    {
        $cacheKey = "distribution_point_{$distributionPointId}_other_count";

        try {
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($distributionPointId) {
                $point = self::find($distributionPointId);
                return $point ? $point->getOtherStatusDevicesCount() : 0;
            });
        } catch (\Exception $e) {
            Log::error('Cache error for other status count: ' . $e->getMessage(), [
                'distribution_point_id' => $distributionPointId
            ]);

            // Fallback to direct query on cache failure
            try {
                $point = self::find($distributionPointId);
                return $point ? $point->getOtherStatusDevicesCount() : 0;
            } catch (\Exception $innerException) {
                Log::error('Fallback query failed: ' . $innerException->getMessage());
                return 0;
            }
        }
    }

    /**
     * Helper method to get badge text for received devices
     * Returns empty string if count is 0
     *
     * @return string
     */
    public static function getReceivedBadgeForPoint(int $pointId): string
    {
        $count = self::getCachedReceivedCount($pointId);
        return $count > 0 ? (string) $count : '';
    }

    /**
     * Helper method to get badge text for other status devices
     * Returns empty string if count is 0
     *
     * @return string
     */
    public static function getOtherStatusBadgeForPoint(int $pointId): string
    {
        $count = self::getCachedOtherStatusCount($pointId);
        return $count > 0 ? (string) $count : '';
    }

    /**
     * Get combined badge text with status indicator for distribution point
     * Format: "received_count/other_count"
     *
     * @return string
     */
    public static function getCombinedBadgeForPoint(int $pointId): string
    {
        $receivedCount = self::getCachedReceivedCount($pointId);
        $otherCount = self::getCachedOtherStatusCount($pointId);

        if ($receivedCount === 0 && $otherCount === 0) {
            return '';
        }

        return "{$receivedCount}/{$otherCount}";
    }

    /**
     * Get badge color based on device counts
     *
     * @return string
     */
    public static function getBadgeColorForPoint(int $pointId): string
    {
        $receivedCount = self::getCachedReceivedCount($pointId);
        $otherCount = self::getCachedOtherStatusCount($pointId);

        if ($otherCount > 0) {
            return 'success'; // Green for active devices
        } elseif ($receivedCount > 0) {
            return 'warning'; // Yellow for received only
        }

        return 'secondary'; // Default color
    }

    /**
     * Clear the cache for this distribution point's device counts
     * Call this when devices are added/removed/status changed
     */
    public function clearDeviceCountCache(): void
    {
        try {
            Cache::forget("distribution_point_{$this->id}_received_count");
            Cache::forget("distribution_point_{$this->id}_other_count");
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage(), [
                'distribution_point_id' => $this->id
            ]);
        }
    }

    /**
     * Get badge configuration for this distribution point
     * Returns an array with badge text and colors for received and other devices
     *
     * @param int $pointId
     * @return array{text: string, receivedColor: string, otherColor: string, receivedRatio: float, otherRatio: float}
     */
    public static function getBadgeConfig(int $pointId): array
    {
        $receivedCount = self::getCachedReceivedCount($pointId);
        $otherCount = self::getCachedOtherStatusCount($pointId);

        // Default configuration
        $config = [
            'text' => '',
            'receivedColor' => 'danger',  // Red for received devices
            'otherColor' => 'warning',    // Amber for other status devices
            'receivedRatio' => 0,
            'otherRatio' => 0
        ];

        // No devices case
        if ($receivedCount === 0 && $otherCount === 0) {
            return $config;
        }

        // Set badge text
        $config['text'] = "{$receivedCount}/{$otherCount}";

        // Calculate ratios
        $total = $receivedCount + $otherCount;
        $config['receivedRatio'] = $receivedCount / $total;
        $config['otherRatio'] = $otherCount / $total;

        return $config;
    }

    /**
     * Get badge text for a distribution point
     *
     * @param int $pointId
     * @return string
     */
    public static function getBadgeText(int $pointId): string
    {
        return self::getBadgeConfig($pointId)['text'];
    }

    /**
     * Get badge color for a distribution point
     *
     * @param int $pointId
     * @return string
     */
    public static function getBadgeColor(int $pointId): string
    {
        return self::getBadgeConfig($pointId)['color'];
    }

    /**
     * Get badge with tooltip information
     *
     * @param int $pointId
     * @return array{badge: string, tooltip: string}
     */
    public static function getBadgeWithTooltip(int $pointId): array
    {
        $receivedCount = self::getCachedReceivedCount($pointId);
        $otherCount = self::getCachedOtherStatusCount($pointId);

        return [
            'badge' => self::getBadgeText($pointId),
            'tooltip' => "{$receivedCount} received, {$otherCount} active devices"
        ];
    }

    /**
     * Get detailed badge color based on device counts and ratios
     *
     * @param int $pointId
     * @return string
     */
    public static function getDetailedBadgeColor(int $pointId): string
    {
        $receivedCount = self::getCachedReceivedCount($pointId);
        $otherCount = self::getCachedOtherStatusCount($pointId);

        // No devices case
        if ($receivedCount === 0 && $otherCount === 0) {
            return 'secondary'; // Gray for no devices
        }

        // Only received devices
        if ($otherCount === 0 && $receivedCount > 0) {
            return 'warning'; // Yellow/amber for received only
        }

        // Only active devices
        if ($receivedCount === 0 && $otherCount > 0) {
            return 'success'; // Green for active only
        }

        // Mixed case - determine color based on ratio
        $ratio = $otherCount / ($receivedCount + $otherCount);

        if ($ratio >= 0.75) {
            return 'success'; // Mostly active (green)
        } elseif ($ratio >= 0.5) {
            return 'info'; // Mixed but more active (blue)
        } elseif ($ratio >= 0.25) {
            return 'warning'; // Mixed but more received (yellow)
        } else {
            return 'danger'; // Mostly received (red)
        }
    }

    /**
     * Get badge HTML with dual colors based on device ratios
     *
     * @param int $pointId
     * @return string
     */
    public static function getDualColorBadgeHtml(int $pointId): string
    {
        $config = self::getBadgeConfig($pointId);

        if (empty($config['text'])) {
            return '';
        }

        // Create a badge with gradient background based on ratios
        return '<span class="badge" style="background: linear-gradient(to right,
                var(--color-danger-500) 0%,
                var(--color-danger-500) ' . ($config['receivedRatio'] * 100) . '%,
                var(--color-warning-500) ' . ($config['receivedRatio'] * 100) . '%,
                var(--color-warning-500) 100%)">' .
                $config['text'] .
            '</span>';
    }
}

