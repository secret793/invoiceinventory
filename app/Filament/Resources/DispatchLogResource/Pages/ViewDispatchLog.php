<?php

namespace App\Filament\Resources\DispatchLogResource\Pages;

use App\Filament\Resources\DispatchLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Route;

class ViewDispatchLog extends ViewRecord
{
    protected static string $resource = DispatchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(fn () => DispatchLogResource::getUrl('index') . '?' . http_build_query([
                    'tableFilters' => [
                        'data_entry_assignment_id' => [
                            'value' => $this->record->data_entry_assignment_id
                        ]
                    ]
                ]))
                ->icon('heroicon-o-arrow-left'),
                
            Actions\EditAction::make()
                ->url(fn () => DispatchLogResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();
        
        // Add a breadcrumb to go back to the filtered list
        if ($this->record->data_entry_assignment_id) {
            $breadcrumbs[1] = [
                'label' => 'Dispatch Logs',
                'url' => DispatchLogResource::getUrl('index') . '?' . http_build_query([
                    'tableFilters' => [
                        'data_entry_assignment_id' => [
                            'value' => $this->record->data_entry_assignment_id
                        ]
                    ]
                ])
            ];
        }
        
        return $breadcrumbs;
    }
}
