<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LongRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'base_usd_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'base_usd_amount' => 'decimal:2',
    ];

    // Ensure the model knows the primary key is 'id' and it's auto-incrementing
    protected $primaryKey = 'id';
    public $incrementing = true;

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}