<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignToAgentResource\Pages;
use App\Models\AssignToAgent;
use App\Models\Device;
use App\Models\Route;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssignToAgentResource extends Resource
{
    protected static ?string $model = AssignToAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Agent Assignments';
    protected static ?string $navigationGroup = 'Data Entry';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('Dispatch date')
                    ->default(now())
                    ->disabled(),
                Forms\Components\TextInput::make('device_id')
                    ->label('Device Serial')
                    ->disabled(),
                Forms\Components\TextInput::make('sad_number')
                    ->label('SAD/T1')
                    ->required(),
                Forms\Components\TextInput::make('vehicle_number')
                    ->required(),
                Forms\Components\Select::make('regime')
                    ->options([
                        'TRANSIT' => 'Transit',
                        'IM4' => 'IM4',
                        'IM7' => 'IM7',
                        'IM8' => 'IM8',
                    ])
                    ->required(),
                Forms\Components\Select::make('route_id')
                    ->label('Route')
                    ->relationship('route', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('manifest_date')
                    ->label('Manifest Date')
                    ->required(),
                Forms\Components\TextInput::make('agency'),
                Forms\Components\TextInput::make('agent_contact'),
                Forms\Components\TextInput::make('truck_number'),
                Forms\Components\TextInput::make('driver_name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device Serial')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sad_number')
                    ->label('SAD/T1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('regime')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('route.name')
                    ->label('Route')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('manifest_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agency'),
                Tables\Columns\TextColumn::make('agent_contact'),
                Tables\Columns\TextColumn::make('truck_number'),
                Tables\Columns\TextColumn::make('driver_name'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('regime')
                    ->options([
                        'TRANSIT' => 'Transit',
                        'IM4' => 'IM4',
                        'IM7' => 'IM7',
                        'IM8' => 'IM8',
                    ]),
                Tables\Filters\SelectFilter::make('route_id')
                    ->label('Route')
                    ->relationship('route', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relations will be added here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignToAgents::route('/'),
            'create' => Pages\CreateAssignToAgent::route('/create'),
            'edit' => Pages\EditAssignToAgent::route('/{record}/edit'),
            'view-allocation' => Pages\ViewAssignmentDataEntry::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }
}