<?php

namespace App\Filament\Resources\DataEntryAssignmentResource\Pages;

use App\Filament\Resources\DataEntryAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataEntryAssignment extends EditRecord
{
    protected static string $resource = DataEntryAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
