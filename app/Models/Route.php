<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    protected $table = 'routes';

    public function assignToAgents(): HasMany
    {
        return $this->hasMany(AssignToAgent::class);
    }
}
