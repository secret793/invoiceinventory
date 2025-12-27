<x-filament::page>
    <x-filament::card>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold tracking-tight">
                    {{ $this->getTitle() }}
                </h2>
                
                <div class="flex space-x-2">
                    <x-filament::button 
                        wire:click="$refresh" 
                        icon="heroicon-o-refresh"
                        color="secondary"
                        size="sm">
                        Refresh
                    </x-filament::button>
                    
                    <x-filament::button 
                        wire:click="$set('tableFiltersOpen', true)" 
                        icon="heroicon-o-filter"
                        color="gray"
                        size="sm">
                        Filters
                    </x-filament::button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                {{ $this->table }}
            </div>
        </div>
    </x-filament::card>
</x-filament::page>
