<x-filament::page>
    <div class="space-y-6">
        <!-- Page Title -->
        <x-filament::card>
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-medium">Device Retrievals</h2>
            </div>
        </x-filament::card>

        <!-- Return to Outstation Button Above the Table -->
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <!-- Return to Outstation Button -->
                <x-filament::button
                    color="danger"
                    wire:click="returnSelectedDevices"
                >
                    Return to Outstation
                </x-filament::button>

                <!-- Entries Dropdown -->
                <div class="flex items-center space-x-2">
                    <label for="entries" class="text-sm font-medium">Show</label>
                    <select wire:model="tableRecordsPerPage" id="entries" class="border rounded p-2 text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-sm font-medium">entries</span>
                </div>
            </div>

            <!-- Table Component -->
            {{ $this->table }}
        </div>
    </div>
</x-filament::page>
