<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Services\ExchangeRateService;
use Filament\Resources\Pages\EditRecord;

class EditReceipt extends EditRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalculate exchange rate if base charge or trucks changed
        $exchangeRateService = app(ExchangeRateService::class);
        $exchangeRate = $exchangeRateService->getGMDPerUSD();
        $data['exchange_rate_used'] = $exchangeRate;

        // Recalculate GMD amounts
        if (isset($data['base_unit_charge_usd']) && $data['base_unit_charge_usd']) {
            $baseCharge = $data['base_unit_charge_usd'];
            $data['unit_charge_gmd'] = $baseCharge * $exchangeRate;
            $data['total_charge_gmd'] = $data['unit_charge_gmd'] * ($data['moving_trucks'] ?? 1);
        }

        // Adjust used if moving_trucks was changed
        if (isset($data['moving_trucks'])) {
            // Only reset if trucks increased (can't decrease active usage)
            if ($data['moving_trucks'] > $this->record->moving_trucks) {
                $data['used'] = $data['moving_trucks'];
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
