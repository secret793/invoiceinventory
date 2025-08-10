<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LongRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        // Add other fillable fields here
    ];

    // Ensure the model knows the primary key is 'id' and it's auto-incrementing
    protected $primaryKey = 'id';
    public $incrementing = true;
}

