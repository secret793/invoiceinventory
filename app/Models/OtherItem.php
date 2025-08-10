<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'quantity',
        'status',
        'date_received',
        'distribution_point_id', // If you want to associate with distribution points
        'type', // Added type field
        'added_by', // Added added_by field
    ];

    public function distributionPoint()
    {
        return $this->belongsTo(DistributionPoint::class);
    }
}
