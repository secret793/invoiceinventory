<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with Search and Count --}}
        <div class="flex justify-between items-center">
            <div class="w-1/3">
                <input
                    type="search"
                    wire:model.debounce.500ms="search"
                    placeholder="Search devices..."
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                />
            </div>
            <div class="bg-primary-600 text-white rounded-full px-4 py-2 shadow-md">
                {{ $devices->count() }} Devices
            </div>
            {{-- Single Dispatch Button Above Table --}}
            <button
                wire:click="openDispatchForm"
                class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded transition"
            >
                Dispatch Devices
            </button>
        </div>

        {{-- Status Filter Buttons --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($statuses as $status)
                <button
                    wire:click="setStatus('{{ $status }}')"
                    class="px-4 py-2 rounded-full text-sm font-medium shadow-md
                        {{ $activeStatus === $status ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}"

                >
                    {{ $status }}
                    <span class="ml-2 bg-white/20 rounded-full px-2 py-0.5 text-xs shadow-inner">
                        {{ $status_counts[$status] ?? 0 }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Devices Table --}}
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">
                            <input
                                type="checkbox"
                                wire:model="selectAll"
                                class="rounded border-gray-300"
                            />
                        </th>
                        <th class="px-4 py-2 text-left font-bold cursor-pointer" wire:click="sort('device_id')">
                            Serial Number
                            @if($sortField === 'device_id')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-2 text-left font-bold">Description</th>
                        <th class="px-4 py-2 text-left font-bold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($devices as $device)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <input
                                    type="checkbox"
                                    wire:model="selectedDevices"
                                    value="{{ $device->id }}"
                                    class="rounded border-gray-300"
                                />
                            </td>
                            <td class="px-4 py-2">{{ $device->device_id }}</td>
                            <td class="px-4 py-2">{{ $device->description }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium shadow-md
                                    {{ $device->status === 'ONLINE' ? 'bg-green-100 text-green-800' :
                                       ($device->status === 'OFFLINE' ? 'bg-red-100 text-red-800' :
                                       'bg-yellow-100 text-yellow-800') }}">

                                    {{ $device->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
