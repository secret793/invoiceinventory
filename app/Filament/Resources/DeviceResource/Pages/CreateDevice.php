<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\DatePicker;
use App\Http\Requests\StoreDeviceRequest;
use Carbon\Carbon;
use App\Models\Device;

class CreateDevice extends CreateRecord
{
    protected static string $resource = DeviceResource::class;

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id(); // Set the user ID
        Log::info('User ID:', ['user_id' => $data['user_id']]); // Log the user ID
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return '/admin/devices';
    }
}
