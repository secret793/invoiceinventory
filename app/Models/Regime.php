<?php

// app/Models/Regime.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Regime extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}