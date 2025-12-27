<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\DeviceRetrieval;
use App\Models\Invoice;
use App\Models\AssignToAgent;
use App\Models\DispatchLog;
use App\Models\ConfirmedAffixed;
use App\Observers\DeviceObserver;
use App\Observers\DeviceRetrievalOverstayObserver;
use App\Observers\DeviceRetrievalStatusSyncObserver;
use App\Observers\DeviceRetrievalObserver;
use App\Observers\InvoiceObserver;
use App\Observers\MonitoringOverstayObserver;
use App\Observers\ReceiptObserver;
use App\Observers\DispatchLogObserver;
use App\Observers\ConfirmedAffixedObserver;
use Illuminate\Support\ServiceProvider;
use App\Observers\DeviceRetrievalAffixLogObserver;
use App\Observers\DeviceRetrievalLogObserver;
use App\Observers\DeviceRetrievalReport2Observer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\OverdueCalculationService::class, function ($app) {
            return new \App\Services\OverdueCalculationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Device::observe(DeviceObserver::class);
        DeviceRetrieval::observe(DeviceRetrievalObserver::class);
        DeviceRetrieval::observe(DeviceRetrievalStatusSyncObserver::class);
        DeviceRetrieval::observe(\App\Observers\OverstayAmountUpdaterRetrieval::class);
        DeviceRetrieval::observe(DeviceRetrievalAffixLogObserver::class);
        DeviceRetrieval::observe(DeviceRetrievalLogObserver::class);
        DeviceRetrieval::observe(DeviceRetrievalReport2Observer::class);
        Invoice::observe(InvoiceObserver::class);
        \App\Models\Monitoring::observe(\App\Observers\MonitoringOverstayObserver::class);
        
        // PHASE 5: Register ReceiptObserver for AssignToAgent model
        AssignToAgent::observe(ReceiptObserver::class);
        
        // PHASE 5: Register DispatchLogObserver for creating finance records
        DispatchLog::observe(DispatchLogObserver::class);
        
        // PHASE 5: Register ConfirmedAffixedObserver for creating finance records
        ConfirmedAffixed::observe(ConfirmedAffixedObserver::class);
    }
}




