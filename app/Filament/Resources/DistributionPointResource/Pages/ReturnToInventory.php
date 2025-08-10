<?php

namespace App\Filament\Resources\DistributionPointResource\Pages;

use App\Models\Device;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Page;

class ReturnToInventory extends Page
{
    protected static string $resource = DistributionPointResource::class;

    public function returnDevices(array $deviceIds)
    {
        if (empty($deviceIds)) {
            Notification::make()
                ->title('Error, No Device selected')
                ->danger()
                ->send();
            return;
        }

        foreach ($deviceIds as $deviceId) {
            $device = Device::find($deviceId);
            $device->status = 'INVENTORY'; // Change status as needed
            $device->save();
        }

        Notification::make()
            ->title('Devices returned to inventory successfully')
            ->success()
            ->send();
    }
}
