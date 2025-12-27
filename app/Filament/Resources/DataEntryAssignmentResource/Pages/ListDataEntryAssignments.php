<?php

namespace App\Filament\Resources\DataEntryAssignmentResource\Pages;

use App\Filament\Resources\DataEntryAssignmentResource;
use App\Models\AllocationPoint;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ListDataEntryAssignments extends ListRecords
{
    protected static string $resource = DataEntryAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Remove custom getTableColumns() to use the resource's table definition
    // This ensures all DataEntryAssignment records are shown with proper columns and filters
   
}
