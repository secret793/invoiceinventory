<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Device;

class DeviceStatisticsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Online', Device::where('status', 'ONLINE')->count())
              //  ->url(route('filament.resources.devices.index')) // Link to the device list view
                ->description('Devices currently online')
                ->descriptionIcon('heroicon-m-signal')
                ->extraAttributes(['class' => 'bg-online']) // Custom class for online
                ->color('success'), // Green for online
            Card::make('Offline', Device::where('status', 'OFFLINE')->count())
                //->url(route('filament.resources.devices.index')) // Link to the device list view
                ->description('Devices currently offline')
                ->descriptionIcon('heroicon-m-signal-slash')
                ->extraAttributes(['class' => 'bg-offline']) // Custom class for offline
                ->color('dark'), // Yellow for offline
            Card::make('Damaged', Device::where('status', 'DAMAGED')->count())
                ->description('Devices that are damaged')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->extraAttributes(['class' => 'bg-damaged']) // Custom class for damaged
                ->color('danger'), // Red for damaged
            Card::make('Fixed', Device::where('status', 'FIXED')->count())
                ->description('Devices that have been fixed')
                ->descriptionIcon('heroicon-m-wrench')
                ->extraAttributes(['class' => 'bg-fixed']) // Custom class for fixed
                ->color('info'), // Blue for fixed
            Card::make('Lost', Device::where('status', 'LOST')->count())
                ->description('Devices that are lost')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->extraAttributes(['class' => 'bg-lost']) // Custom class for lost
                ->color('danger'), // Red for lost
            Card::make('Unconfigured', Device::where('status', 'UNCONFIGURED')->count())
                ->description('Devices pending configuration')
                ->descriptionIcon('heroicon-m-cog')
                ->extraAttributes(['class' => 'bg-unconfigured'])
                ->color('gray'),
        ];
    }
}
