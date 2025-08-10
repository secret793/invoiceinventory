<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Device;

class DevicesAnalyticsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $onlineCount = Device::where('status', 'ONLINE')->count();
        $offlineCount = Device::where('status', 'OFFLINE')->count();
        $damagedCount = Device::where('status', 'DAMAGED')->count();
        $fixedCount = Device::where('status', 'FIXED')->count();
        $lostCount = Device::where('status', 'LOST')->count();
        $receivedCount = Device::where('status', 'RECEIVED')->count();
        $unconfiguredCount = Device::where('status', 'UNCONFIGURED')->count();

        return [
            Card::make('Online', Device::where('status', 'ONLINE')->count())
            //->url(route('admin.devices')) // {{ edit_1 }}
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-signal')
                ->extraAttributes(['class' => Device::where('status', 'ONLINE')->count() > 0 ? 'bg-green-500' : 'bg-gray-500'])
                ->color('success'),
                
            Card::make('Offline', Device::where('status', 'OFFLINE')->count())
            //->url(route('admin.devices')) // {{ edit_1 }}
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-signal-slash')
                ->extraAttributes(['class' => $offlineCount > 0 ? 'bg-orange-500' : 'bg-gray-500'])
                ->color('warning'),

            Card::make('Damaged', Device::where('status', 'DAMAGED')->count())
               // ->url(route('admin.devices')) // {{ edit_1 }}
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->extraAttributes(['class' => $damagedCount > 0 ? 'bg-red-500' : 'bg-gray-500'])
                ->color('danger'),

            Card::make('Fixed', Device::where('status', 'FIXED')->count())
               // ->url(route('admin.devices')) // {{ edit_1 }}
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-wrench')
                ->extraAttributes(['class' => $fixedCount > 0 ? 'bg-blue-500' : 'bg-gray-500'])
                ->color('info'),

            Card::make('Lost', Device::where('status', 'LOST')->count())
               // ->url(route('admin.devices')) // {{ edit_1 }}
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->extraAttributes(['class' => $lostCount > 0 ? 'bg-red-500' : 'bg-gray-500'])
                ->color('danger'),

            Card::make('Received', Device::where('status', 'RECEIVED')->count())
                //->url(route('admin.devices')) // {{ edit_1 }}
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-check-circle')
                ->extraAttributes(['class' => $receivedCount > 0 ? 'bg-green-500' : 'bg-gray-500'])
                ->color('success'),

            Card::make('Unconfigured', $unconfiguredCount)
                ->description('TRACKERS')
                ->descriptionIcon('heroicon-m-cog')
                ->extraAttributes(['class' => $unconfiguredCount > 0 ? 'bg-gray-500' : 'bg-gray-300'])
                ->color('gray'),
        ];
    }
}
