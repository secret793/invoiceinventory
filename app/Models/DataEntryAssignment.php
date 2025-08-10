<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DataEntryAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'allocation_point_id',
        'device_id',
        'status',
        'notes',
        'title',
        'description',
        'user_id'
    ];

    protected static function booted()
    {
        static::addGlobalScope('with-allocation-point', function (Builder $builder) {
            $builder->with('allocationPoint');
        });

        static::addGlobalScope('user-data-entry-points', function (Builder $builder) {
            $user = auth()->user();

            // Allow Super Admin, Warehouse Manager, and Data Entry Officer to see all
            if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
                return;
            }

            // Other roles filtered as before
            $builder->whereHas('allocationPoint', function ($query) use ($user) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user?->id);
                });
            });
        });
    }

    public function allocationPoint()
    {
        return $this->belongsTo(AllocationPoint::class)->withDefault();
    }

    /**
     * Get the dispatch logs for the data entry assignment.
     */
    public function dispatchLogs()
    {
        return $this->hasMany(DispatchLog::class, 'data_entry_assignment_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
