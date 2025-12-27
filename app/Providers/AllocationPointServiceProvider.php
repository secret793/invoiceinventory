<?php

namespace App\Providers;

use App\Models\AllocationPoint;
use Illuminate\Support\ServiceProvider;
use App\Services\AllocationPointPermissionService;

class AllocationPointServiceProvider extends ServiceProvider
{
    public function boot()
    {
        AllocationPoint::created(function ($allocationPoint) {
            $service = app(AllocationPointPermissionService::class);
            $service->generatePermissions();
        });
    }

    public function register()
    {
        //
    }
}
