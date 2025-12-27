<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasNotifications;
use App\Traits\CalculatesOverdueHours;
use Illuminate\Support\Facades\DB;
use App\Models\Device;
use App\Models\Route;
use App\Models\LongRoute;
use App\Models\DeviceRetrieval;

class Monitoring extends Model
{
    use HasFactory, CalculatesOverdueHours, HasNotifications;

    protected $table = 'monitorings';

    protected $fillable = [
        'date',
        'current_date',
        'device_id',
        'boe',
        'sad_number',
        'vehicle_number',
        'regime',
        'destination',
        'route_id',
        'long_route_id',
        'manifest_date',
        'note',
        'agency',
        'agent_contact',
        'truck_number',
        'driver_name',
        'affixing_date',
        'status',
        'overstay_days',
        'retrieval_status'
    ];



    protected $casts = [
        'date' => 'datetime',
        'current_date' => 'datetime',
        'manifest_date' => 'date',
        'affixing_date' => 'datetime',
        'affixing_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($monitoring) {
            // Ensure route_id is null when long_route_id is set and vice versa
            if (!is_null($monitoring->long_route_id)) {
                $monitoring->route_id = null;
            } elseif (!is_null($monitoring->route_id)) {
                $monitoring->long_route_id = null;
            }

            // Check for existing record with same device_id
            $existing = static::where('device_id', $monitoring->device_id)
                ->where('id', '!=', $monitoring->id)
                ->first();

            if ($existing) {
                // Delete the existing record
                $existing->delete();
            }
        });

        // Log route assignment changes
        static::updating(function ($monitoring) {
            if ($monitoring->isDirty(['route_id', 'long_route_id'])) {
                \Log::info('Route assignment changed', [
                    'monitoring_id' => $monitoring->id,
                    'old_route_id' => $monitoring->getOriginal('route_id'),
                    'new_route_id' => $monitoring->route_id,
                    'old_long_route_id' => $monitoring->getOriginal('long_route_id'),
                    'new_long_route_id' => $monitoring->long_route_id,
                    'changed_at' => now(),
                    'changed_by' => auth()->id() ?? 'system'
                ]);
            }
        });
    }

    public function addNewNote(string $note, ?string $manifestDate = null): bool
    {
        try {
            // Using Query Builder for direct database access
            DB::table($this->table)
                ->where('id', $this->id)
                ->update([
                    'note' => $note,
                    'manifest_date' => $manifestDate,
                    'updated_at' => now()
                ]);

            // Refresh the model to get the new data
            $this->refresh();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to add note to monitoring: ' . $e->getMessage());
            return false;
        }
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    public function deviceRetrievals()
    {
        return $this->hasMany(DeviceRetrieval::class, 'device_id', 'device_id')
            ->whereIn('retrieval_status', ['RETRIEVED', 'NOT_RETRIEVED'])
            ->latest();
    }

    /**
     * Get the latest device retrieval for this monitoring record
     */
    public function latestDeviceRetrieval()
    {
        return $this->hasOne(DeviceRetrieval::class, 'device_id', 'device_id')
            ->latest();
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function longRoute()
    {
        return $this->belongsTo(LongRoute::class, 'long_route_id');
    }

    /**
     * Get the device retrieval record associated with this monitoring.
     */
    public function deviceRetrieval()
    {
        // First try to find by monitoring_id
        $byMonitoringId = $this->hasOne(DeviceRetrieval::class, 'monitoring_id', 'id');
        
        // If no record found by monitoring_id, try to find by device_id
        if (!$byMonitoringId->exists()) {
            return $this->hasOne(DeviceRetrieval::class, 'device_id', 'device_id')
                ->whereNull('monitoring_id')
                ->orWhere('monitoring_id', $this->id);
        }
        
        return $byMonitoringId;
    }

    /**
     * Check if this monitoring has a long route assigned
     */
    public function isLongRoute(): bool
    {
        return !is_null($this->long_route_id);
    }

    /**
     * Check if this monitoring has a regular route assigned
     */
    public function isRegularRoute(): bool
    {
        return !is_null($this->route_id);
    }

    /**
     * Get the grace period in hours based on route type
     */
    public function getGracePeriodHours(): int
    {
        return $this->isLongRoute() ? 48 : 24;
    }
}
