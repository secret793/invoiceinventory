<?php

namespace App\Filament\Resources;

use App\Models\AllocationPoint;
use App\Filament\Resources\AllocationPointResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AllocationPointResource extends Resource
{
    protected static ?string $model = AllocationPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('location')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('dataEntryAssignments.count')->label('Data Entry Assignments Count'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn (AllocationPoint $record) =>
                        auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']) ||
                        auth()->user()?->hasPermissionTo('view_allocationpoint_' . Str::slug($record->name))
                    ),
                Tables\Actions\EditAction::make()
                    ->visible(fn () =>
                        auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer'])
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () =>
                        auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer'])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('Warehouse Manager')),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('Warehouse Manager')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllocationPoints::route('/'),
            'create' => Pages\CreateAllocationPoint::route('/create'),
            'view' => Pages\ViewAllocationPoint::route('/{record}'),
            'edit' => Pages\EditAllocationPoint::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Allow Super Admin, Warehouse Manager, Distribution Officer, and Allocation Officer to see all points
        if (auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer', 'Allocation Officer'])) {
            return $query;
        }

        // For Data Entry Officer, hide all allocation points
        if (auth()->user()?->hasRole('Data Entry Officer')) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer', 'Allocation Officer']);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer', 'Allocation Officer'])) {
            return true;
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Distribution Officer']);
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $user = auth()->user();

        if ($user->hasRole('Allocation Officer')) {
            $permittedPoints = $user->permissions
                ->filter(fn ($permission) => str_starts_with($permission->name, 'view_allocationpoint_'))
                ->map(fn ($permission) => Str::title(str_replace('-', ' ',
                    Str::after($permission->name, 'view_allocationpoint_')
                )))
                ->toArray();

            $query->whereIn('name', $permittedPoints);
        }

        return $query;
    }

    /**
     * Get the navigation badge for the resource.
     *
     * @return string|null
     */
    public static function getNavigationBadge(): ?string
    {
        // Count allocation points with received devices
        $receivedCount = \App\Models\AllocationPoint::whereHas('devices', function ($query) {
            $query->where('status', 'RECEIVED');
        })->count();

        // Count allocation points with other status devices
        $otherCount = \App\Models\AllocationPoint::whereHas('devices', function ($query) {
            $query->where('status', '!=', 'RECEIVED');
        })->count();

        // If there are any counts, show them
        if ($receivedCount > 0 || $otherCount > 0) {
            return "{$receivedCount}/{$otherCount}";
        }

        return null;
    }

    /**
     * Get the navigation badge color for the resource.
     *
     * @return string|null
     */
    public static function getNavigationBadgeColor(): ?string
    {
        // Check if there are any received devices
        $hasReceived = \App\Models\AllocationPoint::whereHas('devices', function ($query) {
            $query->where('status', 'RECEIVED');
        })->exists();

        if ($hasReceived) {
            return 'danger'; // Red for received devices
        }

        // Check if there are any other status devices
        $hasOther = \App\Models\AllocationPoint::whereHas('devices', function ($query) {
            $query->where('status', '!=', 'RECEIVED');
        })->exists();

        if ($hasOther) {
            return 'warning'; // Yellow/amber for other statuses
        }

        return null;
    }
}





