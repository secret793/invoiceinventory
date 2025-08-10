<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\DeviceRetrieval;
use App\Models\ConfirmedAffixed;
use App\Models\Monitoring;
use App\Models\AllocationPoint;
use App\Models\Store;
use App\Models\Notification;
use App\Models\User;
use App\Models\Destination;
use App\Observers\DeviceObserver;
use App\Observers\DeviceRetrievalObserver;
use App\Observers\ConfirmedAffixedObserver;
use App\Observers\MonitoringObserver;
use App\Observers\CurrentDateObserver;
use App\Observers\AllocationPointObserver;
use App\Observers\AllocationPointObserver2;
use App\Observers\StoreObserver;
use App\Observers\MonitoringDeviceObserver;
use App\Observers\NotificationObserver;
use App\Observers\PermissionStoredObserver;
use App\Observers\DestinationObserver;
use App\Observers\DestinationSyncObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Observers\DeviceRetrievalAffixLogObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // Device Observers
        Device::observe(DeviceObserver::class);

        // Monitoring Observers
        Monitoring::observe(CurrentDateObserver::class);
        Monitoring::observe(MonitoringObserver::class);

        // Store Observer
        Store::observe(StoreObserver::class);

        // Device Retrieval Observer removed - now handled in AppServiceProvider

        // Confirmed Affixed Observer
        ConfirmedAffixed::observe(ConfirmedAffixedObserver::class);

        // Allocation Point Observers
        AllocationPoint::observe(AllocationPointObserver::class);
        AllocationPoint::observe(AllocationPointObserver2::class); // Register our new observer

        // Notification Observer
        Notification::observe(NotificationObserver::class);

        // User Observer
        User::observe(PermissionStoredObserver::class);

        // Destination Observer
        Destination::observe(DestinationObserver::class);

        // Destination Sync Observer
        ConfirmedAffixed::observe(DestinationSyncObserver::class);

        // DeviceRetrievalAffixLogObserver is now registered in AppServiceProvider only
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
