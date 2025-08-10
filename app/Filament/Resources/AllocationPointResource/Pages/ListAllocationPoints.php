<?php

namespace App\Filament\Resources\AllocationPointResource\Pages;

use App\Filament\Resources\AllocationPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListAllocationPoints extends ListRecords
{
    protected static string $resource = AllocationPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('dataEntryAssignments.count')->label('Data Entry Assignments Count'), // Display count of related assignments
            // Add other columns as necessary
        ];
    }
} 