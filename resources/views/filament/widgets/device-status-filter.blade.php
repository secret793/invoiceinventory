<x-filament::widget>
    <div class="flex flex-wrap gap-2 p-4">
        @php
            $statuses = [
                'ONLINE' => ['label' => 'Online', 'color' => 'success'],
                'OFFLINE' => ['label' => 'Offline', 'color' => 'danger'],
                'DAMAGED' => ['label' => 'Damaged', 'color' => 'warning'],
                'FIXED' => ['label' => 'Fixed', 'color' => 'info'],
                'LOST' => ['label' => 'Lost', 'color' => 'gray'],
                'RECEIVED' => ['label' => 'Received', 'color' => 'primary'],
            ];
        @endphp

        @foreach ($statuses as $status => $config)
            <button
                wire:click="filterByStatus('{{ $status }}')"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full transition-colors
                    {{ in_array($status, $selectedStatuses) 
                        ? 'ring-2 ring-primary-500 '
                        : '' }}
                    {{ match($config['color']) {
                        'success' => 'bg-success-500 text-white hover:bg-success-600',
                        'danger' => 'bg-danger-500 text-white hover:bg-danger-600',
                        'warning' => 'bg-warning-500 text-white hover:bg-warning-600',
                        'info' => 'bg-info-500 text-white hover:bg-info-600',
                        'gray' => 'bg-gray-500 text-white hover:bg-gray-600',
                        'primary' => 'bg-primary-500 text-white hover:bg-primary-600',
                        default => 'bg-gray-500 text-white hover:bg-gray-600',
                    } }}"
            >
                {{ $config['label'] }}
            </button>
        @endforeach
    </div>
</x-filament::widget> 