<x-filament-widgets::widget>
    <div class="flex flex-wrap items-center gap-2 px-2 py-1">
        @php
            $statuses = [
                'ONLINE' => 'success',
                'OFFLINE' => 'danger',
                'DAMAGED' => 'danger',
                'FIXED' => 'success',
                'LOST' => 'danger',
                'RECEIVED' => 'warning',
            ];
        @endphp

        @foreach ($statuses as $status => $color)
            <x-filament::button
                size="sm"
                :color="$color"
                wire:click="filterByStatus('{{ $status }}')"
                @class([
                    'ring-2 ring-primary-600' => in_array($status, $this->tableFilters['status']['values'] ?? []),
                ])
            >
                {{ $status }}
            </x-filament::button>
        @endforeach
    </div>
</x-filament-widgets::widget> 