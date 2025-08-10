<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'distribution_point_id',
        'destination',
        'regime',
        'status',
        'dispatched_at',
    ];

    protected $casts = [
        'devices' => 'array', // Automatically cast the JSON column to an array
    ];

    // Relationship to the User model (assuming users can dispatch items)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to the DistributionPoint model
    public function distributionPoint()
    {
        return $this->belongsTo(DistributionPoint::class);
    }
}
