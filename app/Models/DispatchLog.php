<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'data_entry_assignment_id',
        'dispatched_by',
        'dispatched_at',
        'details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dispatched_at' => 'datetime',
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the device that owns the dispatch log.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the data entry assignment that owns the dispatch log.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DataEntryAssignment::class, 'data_entry_assignment_id');
    }

    /**
     * Get the user who dispatched the device.
     */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }




}
