<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DispatchResource\Pages;
use App\Filament\Resources\DispatchResource\RelationManagers;
use App\Models\Dispatch;
use App\Models\DistributionPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MultiSelect;
use Illuminate\Database\Eloquent\Model;

class DispatchResource extends Resource
{
    protected static ?string $model = Dispatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Dispatches';
    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('User')
                    ->options(\App\Models\User::all()->pluck('name', 'id'))
                    ->default(auth()->id())
                    ->required(),
                
                Select::make('distribution_point_id')
                    ->label('Distribution Point')
                    ->options(DistributionPoint::all()->pluck('name', 'id'))
                    ->required(),

                Select::make('destination')
                    ->label('Destination')
                    ->options(\App\Models\Destination::all()->pluck('name', 'id'))
                    ->required(),
                
                MultiSelect::make('devices')
                    ->label('Devices to Dispatch')
                    ->options(function () {
                        return \App\Models\Device::all()->pluck('serial_number', 'id');
                    })
                    ->required()
                    ->columns(2),

                Select::make('regime')
                    ->label('Regime')
                    ->options(\App\Models\Regime::all()->pluck('name', 'id'))
                    ->required(),

                TextInput::make('status')
                    ->default('pending')
                    ->hidden(),

                DatePicker::make('dispatched_at')
                    ->label('Dispatch Date')
                    ->required()
                    ->default(now()),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('distributionPoint.name')->label('Distribution Point')->sortable(),
                TextColumn::make('status')->sortable(),
                TextColumn::make('dispatched_at')->label('Dispatch Date')->date()->sortable(),
                BadgeColumn::make('status')
                    ->enum([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'canceled' => 'Canceled',
                    ])
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'canceled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('confirmDispatch')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Dispatch $record) {
                        $record->update(['status' => 'confirmed']);
                        $record->save();

                        Filament\Notifications\Notification::make()
                            ->title('Dispatch Confirmed!')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Dispatch $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define relationships here if necessary
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatches::route('/'),
            'create' => Pages\CreateDispatch::route('/create'),
            'edit' => Pages\EditDispatch::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Affixing Officer']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Affixing Officer']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Affixing Officer']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Warehouse Manager', 'Affixing Officer']);
    }
}
