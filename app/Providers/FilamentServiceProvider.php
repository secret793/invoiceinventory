<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Filament\Panel;
use Livewire\Livewire;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('confirmed-affix-report-modal', \App\Http\Livewire\ConfirmedAffixReportModal::class);
        
        Filament::serving(function () {
            Panel::configureUsing(function (Panel $panel) {
                $panel
                    ->id('admin')
                    ->path('admin')
                    // Other configurations...
                ;
            });
        });
    }
}
