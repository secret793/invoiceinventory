<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataEntryAssignmentResource\Pages;
use App\Models\DataEntryAssignment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Navigation\NavigationItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DataEntryAssignmentResource extends Resource
{
    protected static ?string $model = DataEntryAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationGroup = 'Assignment Management';

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make()
                ->label('Data Entry Assignments')
                ->icon('heroicon-o-clipboard-document-list')
                ->group('Assignment Management')
                ->sort(1)
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('allocationPoint.name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RETURNED' => 'warning',
                        'PENDING' => 'info',
                        default => 'secondary',
                    }),
                TextColumn::make('notes')
                    ->label('Return Notes')
                    ->wrap()
                    ->words(30)
                    ->tooltip(function ($record) {
                        return $record->notes ?? null;
                    })
                    ->description(fn ($record) => $record->updated_at ? 'Updated: ' . $record->updated_at->format('Y-m-d H:i') : '')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Updated By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('allocation_point')
                    ->relationship('allocationPoint', 'name')
                    ->label('Allocation Point')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'ONLINE' => 'Online',
                        'OFFLINE' => 'Offline',
                        'DAMAGED' => 'Damaged',
                        'FIXED' => 'Fixed',
                        'LOST' => 'Lost',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']) ||
                        auth()->user()?->hasPermissionTo('edit_allocationpoint_' . \Str::slug($record->allocationPoint->name))
                    ),
                Tables\Actions\ViewAction::make(),
                Action::make('assign')
                    ->label('Assign to Agent')
                    ->icon('heroicon-o-user-plus')
                    ->button()
                    ->color('primary')
                    ->visible(fn ($record) => 
                        auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager']) ||
                        auth()->user()?->hasPermissionTo('edit_allocationpoint_' . \Str::slug($record->allocationPoint->name))
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager'])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataEntryAssignments::route('/'),
            'create' => Pages\CreateDataEntryAssignment::route('/create'),
            'view' => Pages\ViewAssignmentDataEntry::route('/{record}'),
            'edit' => Pages\EditDataEntryAssignment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Assignment/Data Entry';
    }

    public static function getLabel(): string
    {
        return 'Assignment/Data Entry';
    }

    public static function getPluralLabel(): string
    {
        return 'Assignments/Data Entry';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }
}
