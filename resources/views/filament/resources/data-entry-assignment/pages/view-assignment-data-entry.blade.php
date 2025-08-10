<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium">{{ $this->getTitle() }}</h2>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex flex-wrap gap-4">
                @if (!$showAssignedToAgent)
                <button wire:click="filterByStatus('ONLINE')" 
                    class="px-4 py-2 text-sm rounded-lg transition-colors duration-200
                    {{ isset($this->tableFilters['status']['values']) && in_array('ONLINE', $this->tableFilters['status']['values']) 
                        ? 'bg-success-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Online
                </button>
                <button wire:click="filterByStatus('OFFLINE')" 
                    class="px-4 py-2 text-sm rounded-lg transition-colors duration-200
                    {{ isset($this->tableFilters['status']['values']) && in_array('OFFLINE', $this->tableFilters['status']['values']) 
                        ? 'bg-danger-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Offline
                </button>
                <button wire:click="filterByStatus('DAMAGED')" 
                    class="px-4 py-2 text-sm rounded-lg transition-colors duration-200
                    {{ isset($this->tableFilters['status']['values']) && in_array('DAMAGED', $this->tableFilters['status']['values']) 
                        ? 'bg-warning-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Damaged
                </button>
                <button wire:click="filterByStatus('FIXED')" 
                    class="px-4 py-2 text-sm rounded-lg transition-colors duration-200
                    {{ isset($this->tableFilters['status']['values']) && in_array('FIXED', $this->tableFilters['status']['values']) 
                        ? 'bg-info-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Fixed
                </button>
                <button wire:click="filterByStatus('LOST')" 
                    class="px-4 py-2 text-sm rounded-lg transition-colors duration-200
                    {{ isset($this->tableFilters['status']['values']) && in_array('LOST', $this->tableFilters['status']['values']) 
                        ? 'bg-gray-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Lost
                </button>
                @endif
                <button wire:click="filterByStatus('ASSIGNED TO AGENT')" 
                    class="px-4 py-2 text-sm rounded-lg transition-colors duration-200
                    {{ $showAssignedToAgent
                        ? 'bg-primary-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $showAssignedToAgent ? 'Show Assigned Devices' : 'Assigned to Agent' }}
                </button>
            </div>
        </div>

        <div class="flex justify-end mb-4">
            @if (!$showAssignedToAgent)
                @foreach ($this->getHeaderActions() as $action)
                    {{ $action }}
                @endforeach
            @endif
            </div>

{{ $this->table }}

{{-- <x-filament::select name="destination_id" label="Destination" :options="$destinations" required /> --}}
</div>
</x-filament-panels::page>