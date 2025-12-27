<div>
    <x-filament-panels::page>
        <div class="space-y-6">
            {{-- Header --}}
            <x-filament::card>
                <h1 class="text-2xl font-bold">{{ $distributionPoint->name }}</h1>
                <p class="text-gray-600">{{ $distributionPoint->description }}</p>
            </x-filament::card>

            {{-- Transfer Buttons Above the Table --}}


            {{-- Status Counts Table --}}
            <div class="bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200 border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 border border-gray-300 text-left">Description</th>
                            <th class="px-4 py-2 border border-gray-300 text-center">OK</th>
                            <th class="px-4 py-2 border border-gray-300 text-center">DAMAGED</th>
                            <th class="px-4 py-2 border border-gray-300 text-center">LOST</th>
                            <th class="px-4 py-2 border border-gray-300 text-center">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-4 py-2 border border-gray-300">35CM LOCKING CABLES</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['35CM LOCKING CABLES']['OK'] ?? 0 }}</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['35CM LOCKING CABLES']['DAMAGED'] ?? 0 }}</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['35CM LOCKING CABLES']['LOST'] ?? 0 }}</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['35CM LOCKING CABLES']['TOTAL'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 border border-gray-300">3 METERS LOCKING CABLES</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['3 METERS LOCKING CABLES']['OK'] ?? 0 }}</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['3 METERS LOCKING CABLES']['DAMAGED'] ?? 0 }}</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['3 METERS LOCKING CABLES']['LOST'] ?? 0 }}</td>
                            <td class="px-4 py-2 border border-gray-300 text-center">{{ $status_counts['3 METERS LOCKING CABLES']['TOTAL'] ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Add this after the Status Counts Table and before the Middle Section --}}
            <div class="bg-white rounded-lg shadow p-4 mt-4">
                <h3 class="text-lg font-medium mb-4">Device Status Statistics</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach ($device_status_counts as $status => $data)
                        <div class="bg-white rounded-lg border p-4 flex flex-col items-center justify-center">
                            <div class="text-sm font-medium text-gray-500">{{ $status }}</div>
                            <div class="mt-2 text-2xl font-semibold
                                {{ match($data['color']) {
                                    'success' => 'text-success-600',
                                    'danger' => 'text-danger-600',
                                    'warning' => 'text-warning-600',
                                    default => 'text-gray-600'
                                } }}">
                                {{ $data['count'] }}
                            </div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ match($data['color']) {
                                        'success' => 'bg-success-100 text-success-800',
                                        'danger' => 'bg-danger-100 text-danger-800',
                                        'warning' => 'bg-warning-100 text-warning-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } }}">
                                    Devices
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Update the total devices count display to use the sum of all status counts --}}
            <div class="bg-orange-500 text-white rounded-full px-3 py-1 text-sm">
                {{ collect($device_status_counts)->sum('count') }} <!-- Total devices -->
            </div>

            {{-- Middle Section with Status Filter Buttons --}}
            <div class="flex items-center justify-between mt-4">
                <nav class="-mb-px flex space-x-4">
                    @foreach ($statuses as $status)
                        <button
                            wire:click="filterDevicesByStatus('{{ $status }}')"
                            class="flex items-center justify-center border border-gray-300 rounded-md py-2 px-4 text-sm font-medium
                                {{ in_array($status, $selectedStatuses) ? 'bg-primary-600 text-white ring-2 ring-primary-600' : 'text-gray-700 hover:bg-gray-100' }}
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
                </nav>
                <div class="bg-orange-500 text-white rounded-full px-3 py-1 text-sm flex items-center gap-2">
                    <span>{{ count($devices) }}</span>
                    @if($search || !empty($selectedStatuses))
                        <span class="text-xs">(Filtered)</span>
                    @endif
                </div>
            </div>

            {{-- Transfer Buttons Below the Sorting Buttons --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <x-filament::button wire:click="collectDevices" color="info" class="w-full">
                    Accept Devices
                </x-filament::button>
                <x-filament::button wire:click="sendToAllocationPoint" color="success" class="w-full">
                    Send to Allocation Point
                </x-filament::button>
                <x-filament::button wire:click="returnDeviceToInventory" color="warning" class="w-full">
                    Return Device to Inventory
                </x-filament::button>
                <x-filament::button wire:click="acceptReturnedDevice" color="primary" class="w-full">
                    Accept Returned Device
                </x-filament::button>
                <x-filament::button wire:click="rejectReturnedDevice" color="danger" class="w-full">
                    Reject Device(s)
                </x-filament::button>
                <x-filament::button
                    wire:click="openChangeStatusModal"
                    color="info"
                    class="w-full">
                    Change Device Status
                </x-filament::button>
            </div>

            {{-- Device Selection --}}
            <x-filament::card>
                <div class="space-y-4">
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
                                        <td colspan="10" class="text-center py-4 border border-gray-300">No devices found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Total Device Count with Status Breakdown --}}
                    <div class="mt-4 p-4 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-lg font-medium text-gray-900">Total Devices:</span>
                                <span class="text-2xl font-bold text-primary-600 ml-2">
                                    {{ \App\Models\Device::where('distribution_point_id', $distributionPoint->id)->count() }}
                                </span>
                            </div>
                            @if($search || !empty($selectedStatuses))
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">Filtered Results:</span>
                                    <span class="ml-2">{{ count($devices) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-filament::card>

            {{-- Allocation Point Modal --}}
            <div x-data="{ open: false }"
                 x-show="open"
                 @open-allocation-modal.window="open = true"
                 @close-allocation-modal.window="open = false"
                 class="relative z-50"
                 style="display: none;">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center sm:items-center">
                        <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                             @click.away="open = false">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        Send to Allocation Point
                                    </h3>
                                    <div class="mt-4">
                                        <label for="allocationPoint" class="block text-sm font-medium text-gray-700">Select Allocation Point:</label>
                                        <select wire:model="selectedAllocationPoint" id="allocationPoint" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Select Allocation Point --</option>
                                            @foreach ($allocationPoints as $point)
                                                <option value="{{ $point->id }}">{{ $point->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                                <x-filament::button
                                    wire:click="confirmSendToAllocationPoint"
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

            {{-- Error Messages --}}
            @if ($errorMessage)
                <div class="mt-4">
                    <div class="alert alert-danger">
                        {{ $errorMessage }}
                    </div>
                </div>
            @endif
        </div>
    </x-filament-panels::page>

    {{-- Change Status Modal --}}
    <div x-data="{ open: false }"
         x-show="open"
         @open-status-modal.window="open = true"
         @close-status-modal.window="open = false"
         class="relative z-50"
         style="display: none;">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center sm:items-center">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                     @click.away="open = false">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                Change Device Status
                            </h3>
                            <div class="mt-4">
                                <label for="deviceStatus" class="block text-sm font-medium text-gray-700">Select New Status:</label>
                                <select wire:model="selectedStatus" id="deviceStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">-- Select Status --</option>
                                    @foreach ($availableStatuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                @if($errorMessage)
                                    <div class="mt-2 text-sm text-red-600">
                                        {{ $errorMessage }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <x-filament::button
                            wire:click="changeDeviceStatus"
                            color="success"
                        >
                            Update Status
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
</div>


