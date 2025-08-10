<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
    
    // Redirect to list page after creation
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}