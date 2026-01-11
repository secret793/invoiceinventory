<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Services\ExchangeRateService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateReceipt extends CreateRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate that either route_id or long_route_id is selected
        if (empty($data['route_id']) && empty($data['long_route_id'])) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body('Please select either Route or Long Route.')
                ->danger()
                ->send();
            
            $this->halt();
        }
        
        // Generate receipt number
        $data['receipt_number'] = $this->generateReceiptNumber();
        
        // Get exchange rate from service
        $exchangeRateService = app(ExchangeRateService::class);
        $exchangeRate = $exchangeRateService->getGMDPerUSD();
        $data['exchange_rate_used'] = $exchangeRate;

        // Calculate GMD amounts if USD charge is available
        if (isset($data['base_unit_charge_usd']) && $data['base_unit_charge_usd']) {
            $baseCharge = $data['base_unit_charge_usd'];
            $data['unit_charge_gmd'] = $baseCharge * $exchangeRate;
            $data['total_charge_gmd'] = $data['unit_charge_gmd'] * ($data['moving_trucks'] ?? 1);
        }

        // Set usage equal to moving trucks
        $data['used'] = $data['moving_trucks'] ?? 0;

        // Set creator and generated_by_user to current authenticated user
        $data['created_by'] = Auth::id();
        $data['generated_by_user'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function generateReceiptNumber(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return 'REC-' . $timestamp . '-' . $random;
    }
}
