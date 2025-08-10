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

   
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('allocationPoint.name')->label('Allocation Point'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ];
    }
   
}
