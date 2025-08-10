<?php

// app/Models/Destination.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'regime_id',
        'address',
        'latitude',
        'longitude',
        'default_location',
        'status',
    ];

    protected static function booted()
    {
        static::addGlobalScope('destination-access', function (Builder $builder) {
            $user = auth()->user();

            // Super Admin and Warehouse Manager can see all destinations
            // No filtering needed for these roles
            if ($user?->hasRole(['Super Admin', 'Warehouse Manager'])) {
                return;
            }

            // For Retrieval Officers, only show destinations they have permission for
            if ($user?->hasRole('Retrieval Officer')) {
                // DESTINATION SORTING LOGIC COMMENTED OUT
                /*
                // Get all permissions that start with 'view_destination_'
                // and extract the destination names from them
                $permissionNames = collect($user->permissions)
                    ->filter(fn ($permission) => str_starts_with($permission->name, 'view_destination_'))
                    ->map(fn ($permission) => Str::title(str_replace('-', ' ',
                        Str::after($permission->name, 'view_destination_')
                    )));

                // Special case: If user has both 'soma' and 'farefeni' permissions,
                // show only those destinations (this appears to be a business rule)
                $hasBothPermissions = $permissionNames->contains('Soma') && $permissionNames->contains('Farefeni');

                if ($hasBothPermissions || $permissionNames->isEmpty()) {
                    // If user has both permissions or no specific permissions,
                    // limit to only Soma and Farefeni destinations
                    $builder->whereIn('name', ['Soma', 'Farefeni']);
                } else {
                    // Otherwise, show only the destinations the user has explicit permissions for
                    $builder->whereIn('name', $permissionNames);
                }
                */

                // TEMPORARILY ALLOW ALL DESTINATIONS
                // This effectively disables destination-based filtering
                return;
            }
        });
    }

    public function regime(): BelongsTo
    {
        return $this->belongsTo(Regime::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the permission name for this destination
     */
    public function getPermissionName(string $type = 'view'): string
    {
        return "{$type}_destination_" . Str::slug($this->name);
    }

    /**
     * Get all permissions for this destination
     */
    public function getAllPermissions(): array
    {
        return [
            $this->getPermissionName('view'),
            $this->getPermissionName('edit'),
            $this->getPermissionName('delete'),
            $this->getPermissionName('manage_devices'),
            $this->getPermissionName('assign_devices'),
            $this->getPermissionName('track_devices'),
            $this->getPermissionName('view_devices'),
            $this->getPermissionName('manage_routes'),
            $this->getPermissionName('create_routes'),
            $this->getPermissionName('edit_routes'),
            $this->getPermissionName('view_routes')
        ];
    }

    /**
     * Check if user has specific permission for this destination
     */
    public function userHasPermission(User $user, string $type = 'view'): bool
    {
        return $user->hasPermissionTo($this->getPermissionName($type));
    }
}


