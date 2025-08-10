<div>
    <x-filament-panels::page>
        <div class="space-y-6">
            {{-- Header --}}
            <x-filament::card>
                <h1 class="text-2xl font-bold">{{ $record->name }}</h1>
            </x-filament::card>

            {{-- Status Counts Table --}}
            

            {{-- Transfer Buttons Above the Device Table --}}
            <div class="flex justify-center gap-4 mt-4 mb-4">
                <x-filament::button wire:click="collectDevices" color="info">
                    Accept Devices
                </x-filament::button>
                <x-filament::button wire:click="sendToAllocationPoint" color="success">
                    Send to Allocation Point
                </x-filament::button>
                <x-filament::button wire:click="returnDeviceToInventory" color="warning">
                    Return to Inventory
                </x-filament::button>
                <x-filament::button wire:click="openChangeStatusModal" color="info">
                    Change Device Status
                </x-filament::button>
            </div>

            {{-- Status Filter Buttons --}}
            <div class="flex justify-center mt-4 mb-4">
                <div class="flex space-x-4">
                    @foreach ($statuses as $status)
                        <button
                            wire:click="filterByStatus('{{ $status }}')"
                            class="flex items-center justify-center border border-gray-300 rounded-md py-2 px-4 text-sm font-medium
                                {{ $selectedStatus === $status ? 'bg-primary-600 text-white ring-2 ring-primary-600' : 'text-gray-700 hover:bg-gray-100' }}
                                {{ isset($device_status_counts[$status]) && $device_status_counts[$status]['count'] > 0 ? '' : 'opacity-50 cursor-not-allowed' }}"
                            {{ isset($device_status_counts[$status]) && $device_status_counts[$status]['count'] > 0 ? '' : 'disabled' }}
                        >
                            <span class="mr-2">
                                @if ($status === 'ONLINE')
                                    <i class="fas fa-circle text-success-500"></i>
                                @elseif ($status === 'OFFLINE')
                                    <i class="fas fa-circle text-danger-500"></i>
                                @elseif ($status === 'DAMAGED')
                                    <i class="fas fa-circle text-warning-500"></i>
                                @elseif ($status === 'FIXED')
                                    <i class="fas fa-circle text-info-500"></i>
                                @elseif ($status === 'LOST')
                                    <i class="fas fa-circle text-gray-500"></i>
                                @elseif ($status === 'RECEIVED')
                                    <i class="fas fa-circle text-purple-500"></i>
                                @endif
                            </span>
                            {{ $status }}
                            <span class="ml-2 text-xs">({{ $device_status_counts[$status]['count'] ?? 0 }})</span>
                        </button>
                    @endforeach
                    @if($selectedStatus)
                        <button 
                            wire:click="clearStatusFilter" 
                            class="flex items-center justify-center border border-gray-300 rounded-md py-2 px-4 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            <span class="mr-2">
                                <i class="fas fa-times text-gray-500"></i>
                            </span>
                            Clear Filter
                        </button>
                    @endif
                </div>
            </div>

            {{-- Search Bar with Clickable Icon --}}
            <div class="flex items-center justify-end gap-2 mb-4">
                <div class="relative w-96">
                    <input
                        type="text"
                        wire:model.defer="search"
                        wire:keydown.enter="performSearch"
                        placeholder="Search devices..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                    <button
                        wire:click="performSearch"
                        class="absolute inset-y-0 left-0 pl-3 flex items-center hover:text-primary-500 cursor-pointer"
                    >
                        <svg class="h-5 w-5 text-gray-400 hover:text-primary-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    @if($search)
                        <button
                            wire:click="clearSearch"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                        >
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 011.414 0L10 8.586l4.293-4.293a1 1 111.414 1.414L11.414 10l4.293 4.293a1 1 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 01-1.414-1.414L8.586 10 4.293 5.707a1 1 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endif
                </div>
                @if($isSearching)
                    <span class="text-sm text-gray-600">
                        Found {{ count($devices) }} results
                    </span>
                @endif
            </div>

            {{-- Device Selection --}}
            <x-filament::card>
                <div class="space-y-4">
                    {{-- Device Table --}}
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200 border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 border border-gray-300"><input type="checkbox" wire:model="selectAll" /></th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Device ID</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Device Type</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Batch Number</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Receipt Date</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Status</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">SIM Number</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">SIM Operator</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Added By</th>
                                    <th class="px-4 py-2 border border-gray-300 text-left">Added On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($devices as $device)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 border border-gray-300"><input type="checkbox" wire:model="selectedDevices" value="{{ $device->id }}" /></td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->device_id }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->device_type }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->batch_number }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->date_received }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->status }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->sim_number }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->sim_operator }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->user->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 border border-gray-300">{{ $device->created_at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-2 text-center text-gray-500">No devices found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-filament::card>

            {{-- Error Messages --}}
            @if ($errorMessage)
                <div class="mt-4">
                    <div class="alert alert-danger">
                        {{ $errorMessage }}
                    </div>
                </div>
            @endif

            <div class="mt-4 flex justify-end space-x-4 text-sm">
                <div class="p-2 bg-gray-100 rounded">
                    <span class="font-medium">Filtered Devices:</span>
                    <span class="ml-1">{{ $this->filteredDeviceCount }}</span>
                </div>
                <div class="p-2 bg-gray-100 rounded">
                    <span class="font-medium">Total Devices:</span>
                    <span class="ml-1">{{ $this->totalDeviceCount }}</span>
                </div>
            </div>
        </div>
    </x-filament-panels::page>

    {{-- Allocation Point Modal --}}
    <div x-data="{ open: false }"
         x-show="open"
         @open-allocation-modal.window="open = true"
         @close-allocation-modal.window="open = false"
         class="relative z-50"
         style="display: none;">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                Transfer Devices to Another Allocation Point
                            </h3>
                            <div class="mt-4">
                                <label for="allocationPoint" class="block text-sm font-medium text-gray-700">
                                    Select Target Allocation Point:
                                </label>
                                <select wire:model="selectedAllocationPoint" id="allocationPoint" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">-- Select Allocation Point --</option>
                                    @foreach ($allocationPoints as $point)
                                        @if($point->id !== $record->id)
                                            <option value="{{ $point->id }}">{{ $point->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <x-filament::button
                            wire:click="processAllocationTransfer"
                            color="success"
                        >
                            Send
                        </x-filament::button>
                        <x-filament::button
                            @click="open = false"
                            color="gray"
                        >
                            Cancel
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Change Status Modal --}}
    <div x-data="{ open: false }"
         x-show="open"
         @open-status-modal.window="open = true"
         @close-status-modal.window="open = false"
         class="relative z-50"
         style="display: none;">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Change Device Status</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Select a new status for the selected devices.
                                </p>
                                @if($errorMessage)
                                    <div class="mt-2 text-sm text-red-600">{{ $errorMessage }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="selectedStatus" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">Select a status</option>
                                @foreach($availableStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        <button wire:click="changeDeviceStatus" type="button" class="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:col-start-2 sm:text-sm">
                            Update Status
                        </button>
                        <button wire:click="closeChangeStatusModal" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:col-start-1 sm:mt-0 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>












