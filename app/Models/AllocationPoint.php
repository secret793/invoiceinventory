<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AllocationPoint extends Model
{
    use HasFactory;

    protected $table = 'allocation_points';
    public $timestamps = true;
    protected $fillable = ['name', 'location', 'status'];
    
    // Cast the status field to ensure type consistency
    protected $casts = [
        'status' => 'string',
    ];
    
    // Enable mass assignment for all fields
    protected $guarded = [];

    /**
     * Get the permission name for this allocation point
     */
    public function getPermissionName(string $type = 'view'): string
    {
        return "{$type}_allocationpoint_" . Str::slug($this->name);
    }

    /**
     * Get all permissions for this allocation point
     */
    public function getAllPermissions(): array
    {
        return [
            $this->getPermissionName('view'),
            $this->getPermissionName('edit'),
            $this->getPermissionName('delete'),
        ];
    }

    /**
     * Check if user has specific permission for this allocation point
     */
    public function userHasPermission(User $user, string $type = 'view'): bool
    {
        return $user->hasPermissionTo($this->getPermissionName($type));
    }

    /**
     * Get users relationship
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'allocation_point_user');
    }

    /**
     * Get devices relationship
     */
    public function devices(): HasMany
    {
        $query = $this->hasMany(Device::class);

        $user = auth()->user();
        if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer', 'Data Entry Officer'])) {
            return $query;
        }

        // Add any additional access controls for other roles if needed
        return $query;
    }

    /**
     * Get the data entry permission name for this allocation point
     */
    public function getDataEntryPermissionName(string $type = 'view'): string
    {
        return "{$type}_data_entry_" . Str::slug($this->name);
    }

    /**
     * Check if user has data entry permission for this allocation point
     */
    public function userHasDataEntryPermission(User $user, string $type = 'view'): bool
    {
        return $user->hasPermissionTo($this->getDataEntryPermissionName($type));
    }

    /**
     * Get all data entry permissions for this allocation point
     */
    public function getDataEntryPermissions(): array
    {
        return [
            $this->getDataEntryPermissionName('view'),
            $this->getDataEntryPermissionName('edit'),
            $this->getDataEntryPermissionName('delete'),
        ];
    }

    /**
     * Scope for data entry access
     */
    public function scopeDataEntryAccess(Builder $query): Builder
    {
        return $query->whereHas('users', function ($q) {
            $q->where('users.id', auth()->id());
        });
    }

    /**
     * Get the permissions associated with this allocation point
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'name', 'name')
            ->where('name', 'like', 'view_allocationpoint_' . Str::slug($this->name));
    }

    public function dataEntryAssignments()
    {
        return $this->hasMany(DataEntryAssignment::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('user-allocation-points', function (Builder $builder) {
            $user = auth()->user();

            // Super Admin, Warehouse Manager, Distribution Officer, Data Entry Officer, and Allocation Officer can see all points
            if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer', 'Data Entry Officer', 'Allocation Officer'])) {
                return;
            }

            // Other roles filtered by user relationship
            $builder->where(function ($query) use ($user) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user?->id);
                });
            });
        });
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
                'allocation_point_id' => $this->id,
                'exception' => $e
            ]);
            return 0;
        }
    }

    /**
     * Get count of devices with DAMAGED status
     *
     * @return int
     */
    public function getDamagedDevicesCount(): int
    {
        try {
            return $this->devices()->where('status', 'DAMAGED')->count();
        } catch (\Exception $e) {
            Log::error('Error counting damaged devices: ' . $e->getMessage(), [
                'allocation_point_id' => $this->id,
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
    public static function getCachedReceivedCount(int $allocationPointId): int
    {
        $cacheKey = "allocation_point_{$allocationPointId}_received_count";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($allocationPointId) {
            $allocationPoint = self::find($allocationPointId);
            return $allocationPoint ? $allocationPoint->getReceivedDevicesCount() : 0;
        });
    }

    /**
     * Get cached damaged devices count with automatic cache invalidation
     *
     * @return int
     */
    public static function getCachedDamagedCount(int $allocationPointId): int
    {
        $cacheKey = "allocation_point_{$allocationPointId}_damaged_count";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($allocationPointId) {
            $allocationPoint = self::find($allocationPointId);
            return $allocationPoint ? $allocationPoint->getDamagedDevicesCount() : 0;
        });
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
                'allocation_point_id' => $this->id,
                'exception' => $e
            ]);
            return 0;
        }
    }

    /**
     * Get cached other status devices count with automatic cache invalidation
     *
     * @return int
     */
    public static function getCachedOtherStatusCount(int $allocationPointId): int
    {
        $cacheKey = "allocation_point_{$allocationPointId}_other_status_count";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($allocationPointId) {
            $allocationPoint = self::find($allocationPointId);
            return $allocationPoint ? $allocationPoint->getOtherStatusDevicesCount() : 0;
        });
    }

    /**
     * Get combined badge text with status indicator for allocation point
     * Format: "received_count/other_count"
     *
     * @return string
     */
    public static function getBadgeText(int $pointId): string
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
    public static function getBadgeColor(int $pointId): string
    {
        $receivedCount = self::getCachedReceivedCount($pointId);

        if ($receivedCount > 0) {
            return 'danger'; // Red for received devices
        }

        $otherCount = self::getCachedOtherStatusCount($pointId);

        if ($otherCount > 0) {
            return 'warning'; // Yellow/amber for other statuses
        }

        return 'secondary'; // Default color
    }

    /**
     * Clear the cache for this allocation point's device counts
     * Call this when devices are added/removed/status changed
     */
    public function clearDeviceCountCache(): void
    {
        try {
            Cache::forget("allocation_point_{$this->id}_received_count");
            Cache::forget("allocation_point_{$this->id}_damaged_count");
            Cache::forget("allocation_point_{$this->id}_other_status_count");
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage(), [
                'allocation_point_id' => $this->id
            ]);
        }
    }

    /**
     * Get badge configuration for this allocation point
     * Returns an array with badge text and colors for received and damaged devices
     *
     * @param int $pointId
     * @return array{text: string, receivedColor: string, damagedColor: string, receivedRatio: float, damagedRatio: float}
     */
    public static function getBadgeConfig(int $pointId): array
    {
        $receivedCount = self::getCachedReceivedCount($pointId);
        $damagedCount = self::getCachedDamagedCount($pointId);

        // Default configuration
        $config = [
            'text' => '',
            'receivedColor' => 'warning',  // Yellow for received devices
            'damagedColor' => 'danger',    // Red for damaged devices
            'receivedRatio' => 0,
            'damagedRatio' => 0
        ];

        // No devices case
        if ($receivedCount === 0 && $damagedCount === 0) {
            return $config;
        }

        // Set badge text
        $config['text'] = "{$receivedCount}/{$damagedCount}";

        // Calculate ratios
        $total = $receivedCount + $damagedCount;
        $config['receivedRatio'] = $receivedCount / $total;
        $config['damagedRatio'] = $damagedCount / $total;

        return $config;
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
        $damagedCount = self::getCachedDamagedCount($pointId);

        return [
            'badge' => self::getBadgeText($pointId),
            'tooltip' => "{$receivedCount} received, {$damagedCount} damaged devices"
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
        $damagedCount = self::getCachedDamagedCount($pointId);

        // No devices case
        if ($receivedCount === 0 && $damagedCount === 0) {
            return 'secondary'; // Gray for no devices
        }

        // Only received devices
        if ($damagedCount === 0 && $receivedCount > 0) {
            return 'warning'; // Yellow/amber for received only
        }

        // Only damaged devices
        if ($receivedCount === 0 && $damagedCount > 0) {
            return 'danger'; // Red for damaged only
        }

        // Mixed case - determine color based on ratio
        $ratio = $damagedCount / ($receivedCount + $damagedCount);

        if ($ratio >= 0.75) {
            return 'danger'; // Mostly damaged (red)
        } elseif ($ratio >= 0.5) {
            return 'danger'; // Mixed but more damaged (red)
        } elseif ($ratio >= 0.25) {
            return 'warning'; // Mixed but more received (yellow)
        } else {
            return 'warning'; // Mostly received (yellow)
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
                var(--color-warning-500) 0%,
                var(--color-warning-500) ' . ($config['receivedRatio'] * 100) . '%,
                var(--color-danger-500) ' . ($config['receivedRatio'] * 100) . '%,
                var(--color-danger-500) 100%)">' .
                $config['text'] .
            '</span>';
    }

    /**
     * Get badge color for received devices
     *
     * @param int $pointId
     * @return string
     */
    public static function getReceivedBadgeColor(int $pointId): string
    {
        $receivedCount = self::getCachedReceivedCount($pointId);

        if ($receivedCount > 0) {
            return 'danger'; // Red for received devices
        }

        return 'secondary'; // Default color when no received devices
    }

    /**
     * Get badge color for damaged devices
     *
     * @param int $pointId
     * @return string
     */
    public static function getDamagedBadgeColor(int $pointId): string
    {
        $damagedCount = self::getCachedDamagedCount($pointId);

        if ($damagedCount > 0) {
            return 'warning'; // Yellow/amber for damaged devices
        }

        return 'secondary'; // Default color when no damaged devices
    }
}





