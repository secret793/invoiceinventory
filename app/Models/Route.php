<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'amount', 'base_usd_amount'];

    protected $table = 'routes';

    protected $casts = [
        'amount' => 'decimal:2',
        'base_usd_amount' => 'decimal:2',
    ];

    public function assignToAgents(): HasMany
    {
        return $this->hasMany(AssignToAgent::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
