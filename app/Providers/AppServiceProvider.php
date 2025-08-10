<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\DeviceRetrieval;
use App\Models\Invoice;
use App\Observers\DeviceObserver;
use App\Observers\DeviceRetrievalOverstayObserver;
use App\Observers\DeviceRetrievalStatusSyncObserver;
use App\Observers\InvoiceObserver;
use App\Observers\MonitoringOverstayObserver;
use Illuminate\Support\ServiceProvider;
use App\Observers\DeviceRetrievalAffixLogObserver;

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
        DeviceRetrieval::observe(DeviceRetrievalStatusSyncObserver::class);
        DeviceRetrieval::observe(\App\Observers\OverstayAmountUpdaterRetrieval::class);
        DeviceRetrieval::observe(DeviceRetrievalAffixLogObserver::class);
        Invoice::observe(InvoiceObserver::class);
        \App\Models\Monitoring::observe(\App\Observers\MonitoringOverstayObserver::class);
    }
}




