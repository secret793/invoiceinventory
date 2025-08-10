<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource;
use App\Filament\Resources\DataEntryAssignmentResource;
use App\Filament\Resources\DispatchLogResource\Pages;
use App\Models\DispatchLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Request as RequestFacade;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;

class DispatchLogResource extends Resource
{
    protected static ?string $model = DispatchLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('device_id')
                    ->relationship('device', 'device_id')
                    ->required(),
                Select::make('data_entry_assignment_id')
                    ->relationship('assignment', 'id')
                    ->required(),
                Select::make('dispatched_by')
                    ->relationship('dispatcher', 'name')
                    ->required(),
                DateTimePicker::make('dispatched_at')
                    ->required(),
                Textarea::make('details')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable()
                    ->url(fn (DispatchLog $record): string => DeviceResource::getUrl('view', ['record' => $record->device_id])),
                TextColumn::make('assignment.title')
                    ->label('Assignment')
                    ->searchable()
                    ->sortable()
                    ->url(fn (DispatchLog $record): string => DataEntryAssignmentResource::getUrl('view', ['record' => $record->data_entry_assignment_id])),
                TextColumn::make('dispatcher.name')
                    ->label('Dispatched By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dispatched_at')
                    ->label('Dispatch Date/Time')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                TextColumn::make('details.boe')
                    ->label('BOE #')
                    ->searchable(),
                TextColumn::make('details.vehicle_number')
                    ->label('Vehicle #')
                    ->searchable(),
                TextColumn::make('details.destination')
                    ->label('Destination')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('dispatched_at')
                    ->form([
                        DatePicker::make('dispatched_from')
                            ->label('From Date'),
                        DatePicker::make('dispatched_until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dispatched_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('dispatched_at', '>=', $date),
                            )
                            ->when(
                                $data['dispatched_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('dispatched_at', '<=', $date),
                            );
                    }),
                Filter::make('device_id')
                    ->form([
                        TextInput::make('device_id')
                            ->label('Device ID')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['device_id'],
                            fn (Builder $query, $deviceId): Builder => $query->whereHas('device', function ($q) use ($deviceId) {
                                $q->where('device_id', 'like', "%{$deviceId}%");
                            })
                        );
                    })
                    ->columnSpan(1),
                    
                Filter::make('data_entry_assignment_id')
                    ->form([
                        TextInput::make('data_entry_assignment_id')
                            ->label('Assignment ID')
                            ->default(fn () => request()->input('tableFilters.data_entry_assignment_id.value'))
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['data_entry_assignment_id'],
                            fn (Builder $query, $assignmentId): Builder => $query->where('data_entry_assignment_id', $assignmentId)
                        );
                    })
                    ->columnSpan(1),
                Filter::make('boe_number')
                    ->form([
                        TextInput::make('boe_number')
                            ->label('BOE #')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['boe_number'],
                            fn (Builder $query, $boe): Builder => $query->where('details->boe', 'like', "%{$boe}%")
                        );
                    }),
            ])
            ->filtersFormColumns(3)
            ->actions([
                ViewAction::make()
                    ->url(fn (DispatchLog $record): string => static::getUrl('view', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatchLogs::route('/'),
            'create' => Pages\CreateDispatchLog::route('/create'),
            'view' => Pages\ViewDispatchLog::route('/{record}'),
            'edit' => Pages\EditDispatchLog::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('dispatched_at');
    }
}
