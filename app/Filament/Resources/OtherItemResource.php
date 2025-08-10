<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtherItemResource\Pages;
use App\Models\OtherItem;
use App\Models\DistributionPoint; // Ensure this is imported
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Facades\Auth; // Add this import at the top

class OtherItemResource extends Resource
{
    protected static ?string $model = OtherItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection'; // Set the icon for the navigation item

    protected static ?string $navigationGroup = 'Inventory'; // Set the navigation group

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('item_name')
                    ->label('Item Name')
                    ->required(),
                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->required(),
                Select::make('type')
                    ->label('Item Type')
                    ->options([
                        '35cm locking cables' => '35cm locking cables',
                        '3m locking cables' => '3m locking cables',
                        'charging stations' => 'charging stations',
                        'wheel barrows' => 'wheel barrows',
                        'trolley' => 'trolley',
                        '60 meters locking cables' => '60 meters locking cables',
                        'usb charging cables' => 'usb charging cables',
                    ])
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'OK' => 'OK',
                        'NEW' => 'NEW',
                        'DAMAGED' => 'DAMAGED',
                        'LOST' => 'LOST',
                    ])
                    ->required(),
                DatePicker::make('date_received')
                    ->label('Date Received')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_received')->label('Receipt Date')->date()->sortable(),
                TextColumn::make('type')->label('Item Type')->sortable()->searchable(),
                TextColumn::make('quantity')->label('Quantity')->sortable()->searchable(),
                TextColumn::make('status')->sortable()->searchable(),
                TextColumn::make('added_by')->label('Added By')->sortable()->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'OK' => 'OK',
                        'NEW' => 'NEW',
                        'DAMAGED' => 'DAMAGED',
                        'LOST' => 'LOST',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),

                // Bulk action for changing item status
                BulkAction::make('changeStatus')
                    ->label('Change Item Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('status')
                            ->label('New Status')
                            ->options([
                                'OK' => 'OK',
                                'NEW' => 'NEW',
                                'DAMAGED' => 'DAMAGED',
                                'LOST' => 'LOST',
                            ])
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('No items selected')
                                ->danger()
                                ->send();
                            return;
                        }

                        $records->each(function ($record) use ($data) {
                            $record->update(['status' => $data['status']]);
                        });

                        Notification::make()
                            ->title('Item status updated successfully')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOtherItems::route('/'),
            'create' => Pages\CreateOtherItem::route('/create'),
            'edit' => Pages\EditOtherItem::route('/{record}/edit'),
        ];
    }

    public static function create(CreateOtherItem $record): void
    {
        $record->added_by = Auth::id(); // Set the added_by field to the authenticated user's ID
        $record->save();
    }
}
