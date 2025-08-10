<x-filament-widgets::widget>
    <div class="flex flex-wrap items-center gap-2 px-2 py-1">
        @php
            $statuses = [
                'ONLINE' => ['label' => 'Online', 'color' => 'success'],
                'OFFLINE' => ['label' => 'Offline', 'color' => 'gray'],
                'DAMAGED' => ['label' => 'Damaged', 'color' => 'danger'],
                'FIXED' => ['label' => 'Fixed', 'color' => 'info'],
                'LOST' => ['label' => 'Lost', 'color' => 'danger'],
                'RECEIVED' => ['label' => 'Received', 'color' => 'warning'],
            ];
        @endphp

        @foreach ($statuses as $status => $config)
            <x-filament::button
                size="sm"
                :color="$config['color']"
                wire:click="filterByStatus('{{ $status }}')"
                :disabled="!isset($this->statusCounts[$status]) || $this->statusCounts[$status] === 0"
                @class([
                    'ring-2 ring-primary-600' => in_array($status, $this->tableFilters['status']['values'] ?? []),
                    'opacity-50 cursor-not-allowed' => !isset($this->statusCounts[$status]) || $this->statusCounts[$status] === 0,
                ])
            >
                {{ $config['label'] }}
                <span class="ml-2 text-xs">
                    ({{ $this->statusCounts[$status] ?? 0 }})
                </span>
            </x-filament::button>
        @endforeach
    </div>
</x-filament-widgets::widget> 