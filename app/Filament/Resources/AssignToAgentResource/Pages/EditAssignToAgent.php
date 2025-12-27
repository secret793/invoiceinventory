<?php

namespace App\Filament\Resources\AssignToAgentResource\Pages;

use App\Filament\Resources\AssignToAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignToAgent extends EditRecord
{
    protected static string $resource = AssignToAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
