<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Store;

class StoreStatisticsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Devices', Store::count())
                ->description('Total number of devices')
                ->descriptionIcon('heroicon-m-signal')
                ->color('secondary'),

            Card::make('Online Devices', Store::where('status', 'ONLINE')->count())
                ->description('Devices currently online')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Card::make('Offline Devices', Store::where('status', 'OFFLINE')->count())
                ->description('Devices currently offline')
                ->descriptionIcon('heroicon-m-signal-slash')
                ->color('dark'),

            Card::make('Damaged Devices', Store::where('status', 'DAMAGED')->count())
                ->description('Devices that are damaged')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Card::make('Fixed Devices', Store::where('status', 'FIXED')->count())
                ->description('Devices that have been fixed')
                ->descriptionIcon('heroicon-o-wrench')
                ->color('info'),

            Card::make('Lost Devices', Store::where('status', 'LOST')->count())
                ->description('Devices that are lost')
                ->descriptionIcon('heroicon-o-question-mark-circle')
                ->color('danger'),

            Card::make('Configured Devices', Store::where('configured', true)->count())
                ->description('Devices that are configured')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Card::make('Unconfigured Devices', Store::where('status', 'UNCONFIGURED')->count())
                ->description('Devices that are not configured')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('warning'),
        ];
    }
}
