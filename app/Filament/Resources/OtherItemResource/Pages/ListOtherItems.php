<?php

namespace App\Filament\Resources\OtherItemResource\Pages;

use App\Filament\Resources\OtherItemResource;
use App\Models\DistributionPoint; // Ensure this is imported
use App\Models\OtherItem; // Import OtherItem model
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Modal;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Widgets\OtherItemsStatisticsWidget;


class ListOtherItems extends ListRecords
{
    protected static string $resource = OtherItemResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            OtherItemsStatisticsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('Add Inventory')
                ->icon('heroicon-o-plus') // Correct icon for adding
                ->color('success')
                ->label('Add Inventory')
                ->modalHeading('Add Other Item')
                ->modalSubmitActionLabel('Save')
                ->form([
                    TextInput::make('item_name')
                        ->label('Item Name')
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
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
                ])
                ->action(function (array $data) {
                    // Create the new OtherItem
                    OtherItem::create($data);
                    Notification::make()
                        ->title('Other Item Added Successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('Distribute Other Items')
                ->icon('heroicon-o-arrow-right') // Correct icon for distribution
                ->color('success')
                ->modalHeading('Distribute Other Items')
                ->modalSubmitActionLabel('Distribute')
                ->form([
                    Select::make('distribution_point_id')
                        ->label('Distribution Point')
                        ->options(DistributionPoint::pluck('name', 'id')) // Ensure this is correct
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Implement distribution logic here
                    // Example: Distribute the selected items to the specified distribution point
                    Notification::make()
                        ->title('Other Items Distributed Successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('Change Other Items Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalHeading('Change Other Items Status')
                ->modalSubmitActionLabel('Change Status')
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
                ->action(function (array $data) {
                    // Implement status change logic here
                    // Example: Change the status of the selected items
                    Notification::make()
                        ->title('Other Items Status Changed Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }
}
