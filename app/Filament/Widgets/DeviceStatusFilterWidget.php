<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use App\Models\Device;

class DeviceStatusFilterWidget extends Widget
{
    protected static string $view = 'filament.widgets.device-status-filter-widget';
    protected int | string | array $columnSpan = 'full';
    
    public array $tableFilters = [];
    public array $statusCounts = [];

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.resources.devices.index');
    }

    public function mount()
    {
        $this->tableFilters = [
            'status' => [
                'values' => [],
            ],
        ];
        $this->updateStatusCounts();
    }

    public function updateStatusCounts()
    {
        $this->statusCounts = Device::query()
            ->whereNotIn('status', ['UNCONFIGURED'])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function filterByStatus(string $status)
    {
        // If the clicked status is the same as the currently active one, clear the filter
        if ($this->tableFilters['status']['values'] === [$status]) {
            $this->tableFilters['status']['values'] = [];
        } else {
            // Otherwise, set the filter to only show the clicked status
            $this->tableFilters['status']['values'] = [$status];
        }

        $this->dispatch('filter-devices-by-status', statuses: $this->tableFilters['status']['values']);
    }
}