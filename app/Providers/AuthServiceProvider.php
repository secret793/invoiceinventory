<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Device;
use App\Models\Store;
use App\Models\Transfer;
use App\Policies\UserPolicy;
use App\Policies\DevicePolicy;
use App\Policies\StorePolicy;
use App\Policies\TransferPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Device::class => DevicePolicy::class,
        Store::class => StorePolicy::class,
        Transfer::class => TransferPolicy::class,
        \App\Models\DataEntryAssignment::class => \App\Policies\DataEntryAssignmentPolicy::class,
        \App\Models\DispatchLog::class => \App\Policies\DispatchLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super Admin Gate - Always grant access to Super Admin
        Gate::before(function (User $user) {
            if ($user->hasRole('Super Admin')) {
                Log::info('AuthServiceProvider: Super Admin access granted', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ]);
                return true;
            }
            
            Log::info('AuthServiceProvider: User access check', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'is_warehouse_manager' => $user->hasRole('Warehouse Manager'),
            ]);
            
            return null; // Continue with normal authorization
        });

        // Custom Gates for Transfer Actions
        Gate::define('approve-transfer', [TransferPolicy::class, 'approve']);
        Gate::define('reject-transfer', [TransferPolicy::class, 'reject']);
    }
}
