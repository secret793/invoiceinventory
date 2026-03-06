<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\AllocationPoint;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function allocationPoints()
    {
        return $this->belongsToMany(AllocationPoint::class, 'allocation_point_user');
    }

    /**
     * Get the dispatch logs where this user is the dispatcher.
     */
    public function dispatchedLogs()
    {
        return $this->hasMany(DispatchLog::class, 'dispatched_by');
    }

    public function dataEntryAssignments()
    {
        return $this->hasMany(DataEntryAssignment::class);
    }

    public function permissionStored()
    {
        return $this->hasMany(PermissionStored::class);
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            $user->roles()->detach();
            $user->permissions()->detach();
            $user->permissionStored()->delete();
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Your secure logic here (temporary return true for testing)
        return true;

        // Recommended secure version (change this!)
        // return $this->hasRole('admin') || $this->hasRole('cook');
        // or
        // return $this->email === 'superadmin@example.com';
    }
}
