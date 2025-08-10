<?php

namespace App\Filament\Resources\DistributionPointResource\Pages;

use App\Models\Device;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Page;

class ChangeDeviceStatus extends Page
{
    protected static string $resource = DistributionPointResource::class;

    public function changeStatus(array $deviceIds, string $newStatus)
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
            $device->status = $newStatus; // Set the new status
            $device->save();
        }

        Notification::make()
            ->title('Device status updated successfully')
            ->success()
            ->send();
    }
}
