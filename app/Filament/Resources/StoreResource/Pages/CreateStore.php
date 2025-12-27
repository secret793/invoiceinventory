<?php

namespace App\Filament\Resources\StoreResource\Pages;

use App\Filament\Resources\StoreResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\DatePicker;
use App\Http\Requests\StoreDeviceRequest;
use Carbon\Carbon;
use App\Models\Device;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;


    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_received')
                ->label('Date Received')
                ->required()
                ->minDate(Carbon::now()->subYear()) // Set minimum date to one year back
                ->maxDate(Carbon::now()) // Set maximum date to today
                ->defaultToday() // Set today's date as default
                ->extraAttributes(['name' => 'date_received']), // Ensure the name matches
            // ... other fields ...
        ];
    }

    protected function getRedirectUrl(): string
    {
        return '/admin/devices';
    }
}

