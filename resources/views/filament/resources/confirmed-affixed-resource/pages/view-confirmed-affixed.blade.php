<x-filament::page>
    <div class="space-y-6">
        <!-- Page Header -->
        <x-filament::card>
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-medium">Confirmed Affixed Devices</h2>
            </div>
        </x-filament::card>

        <!-- Actions Above the Table -->
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <!-- Picked for Affixing Button -->
                <x-filament::button
                    wire:click="markAsPicked"
                    color="success"
                >
                    Picked for Affixing
                </x-filament::button>
            </div>

            <!-- Table Component -->
            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">
                                <input type="checkbox" class="rounded border-gray-300">
                            </th>
                            <th class="px-4 py-2 font-medium text-gray-700">Date</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Serial #</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Agency</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Agent Contact</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Truck Number</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Driver Name</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Regime</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Destination</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->table->getRecords() as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    <input type="checkbox" value="{{ $record->id }}" class="rounded border-gray-300">
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->date->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->device->serial }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->agency }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->agent_contact }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->truck_number }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->driver_name }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->regime }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $record->destination }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament::page>
