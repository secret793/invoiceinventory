<!-- Overstay Devices Modal Table View -->
<div class="p-6">
    <!-- Filter Panel -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Filters</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Device ID Filter -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Device ID</label>
                <input 
                    type="text" 
                    wire:model.debounce="tempOverstayFilters.device_id"
                    placeholder="e.g., DEV-001"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- SAD/BOE Filter -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">SAD/BOE</label>
                <input 
                    type="text" 
                    wire:model.debounce="tempOverstayFilters.boe"
                    placeholder="Bill of Entry"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- Invoice Number Filter -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Invoice Number</label>
                <input 
                    type="text" 
                    wire:model.debounce="tempOverstayFilters.invoice_number"
                    placeholder="Invoice #"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- Payment Status Filter -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Payment Status</label>
                <select 
                    wire:model="tempOverstayFilters.payment_status"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All Status</option>
                    <option value="PP">Pending Payment</option>
                    <option value="PD">Paid</option>
                    <option value="WAIVED">Waived</option>
                </select>
            </div>

            <!-- Destination Filter with Autocomplete -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Destination</label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.debounce="destinationSearch"
                        @if(empty($tempOverstayFilters['destination_id']))
                            placeholder="Search destinations..."
                        @else
                            disabled
                        @endif
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                    
                    @if($tempOverstayFilters['destination_id'])
                        <button 
                            type="button"
                            wire:click="clearDestination"
                            class="absolute right-2 top-2 text-gray-500 hover:text-gray-700"
                            title="Clear selection"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    @endif
                    
                    @if(!empty($destinationSearch) && empty($tempOverstayFilters['destination_id']) && !empty($filteredDestinations))
                        <div class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 rounded-b-md shadow-lg z-10 max-h-48 overflow-y-auto">
                            @foreach($filteredDestinations as $destination)
                                <button 
                                    type="button"
                                    wire:click="selectDestination({{ $destination->id }})"
                                    class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm text-gray-900 border-b border-gray-100"
                                >
                                    {{ $destination->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($tempOverstayFilters['destination_id'])
                        <div class="mt-1 px-3 py-2 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-900">
                            @php
                                $selected = $availableDestinations->find($tempOverstayFilters['destination_id']);
                            @endphp
                            {{ $selected?->name ?? 'Selected' }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Allocation Point Filter with Autocomplete -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Allocation Point</label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.debounce="allocationPointSearch"
                        @if(empty($tempOverstayFilters['allocation_point_id']))
                            placeholder="Search allocation points..."
                        @else
                            disabled
                        @endif
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                    
                    @if($tempOverstayFilters['allocation_point_id'])
                        <button 
                            type="button"
                            wire:click="clearAllocationPoint"
                            class="absolute right-2 top-2 text-gray-500 hover:text-gray-700"
                            title="Clear selection"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    @endif
                    
                    @if(!empty($allocationPointSearch) && empty($tempOverstayFilters['allocation_point_id']) && !empty($filteredAllocationPoints))
                        <div class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 rounded-b-md shadow-lg z-10 max-h-48 overflow-y-auto">
                            @foreach($filteredAllocationPoints as $point)
                                <button 
                                    type="button"
                                    wire:click="selectAllocationPoint({{ $point->id }})"
                                    class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm text-gray-900 border-b border-gray-100"
                                >
                                    {{ $point->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($tempOverstayFilters['allocation_point_id'])
                        <div class="mt-1 px-3 py-2 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-900">
                            @php
                                $selected = $availableAllocationPoints->find($tempOverstayFilters['allocation_point_id']);
                            @endphp
                            {{ $selected?->name ?? 'Selected' }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Overstay Days Range -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Min Overstay Days</label>
                <input 
                    type="number" 
                    wire:model.debounce="tempOverstayFilters.overstay_days_min"
                    min="0"
                    placeholder="Min"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Max Overstay Days</label>
                <input 
                    type="number" 
                    wire:model.debounce="tempOverstayFilters.overstay_days_max"
                    min="0"
                    placeholder="Max"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- Overstay Amount Range -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Min Amount (GMD)</label>
                <input 
                    type="number" 
                    wire:model.debounce="tempOverstayFilters.overstay_amount_min"
                    min="0"
                    step="0.01"
                    placeholder="Min"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Max Amount (GMD)</label>
                <input 
                    type="number" 
                    wire:model.debounce="tempOverstayFilters.overstay_amount_max"
                    min="0"
                    step="0.01"
                    placeholder="Max"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- Date Range -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                <input 
                    type="date" 
                    wire:model="tempOverstayFilters.start_date"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                <input 
                    type="date" 
                    wire:model="tempOverstayFilters.end_date"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
        </div>

        <!-- Filter Action Buttons -->
        <div class="flex gap-2 mt-4">
            <button 
                type="button"
                wire:click="applyOverstayFilters"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition"
            >
                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                Apply Filters
            </button>

            @if($hasActiveOverstayFilters)
                <button 
                    type="button"
                    wire:click="resetOverstayFilters"
                    class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400 transition"
                >
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset Filters
                </button>
            @endif

            <button 
                type="button"
                wire:click="exportOverstayDevices"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition ml-auto"
            >
                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Excel
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if($overstayStatistics)
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Devices Card -->
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="text-xs font-semibold text-blue-900 mb-1">Total Devices</div>
                <div class="text-2xl font-bold text-blue-600">
                    {{ number_format($overstayStatistics['total_devices']) }}
                </div>
            </div>

            <!-- Total Amount Card -->
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="text-xs font-semibold text-green-900 mb-1">Total Overstay Amount</div>
                <div class="text-2xl font-bold text-green-600">
                    D{{ number_format($overstayStatistics['total_overstay_amount'], 2) }}
                </div>
            </div>

            <!-- Total Days Card -->
            <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="text-xs font-semibold text-orange-900 mb-1">Total Overstay Days</div>
                <div class="text-2xl font-bold text-orange-600">
                    {{ number_format($overstayStatistics['total_overstay_days']) }}
                </div>
            </div>

            <!-- Pending Payment Card -->
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="text-xs font-semibold text-red-900 mb-1">Pending Payment</div>
                <div class="text-2xl font-bold text-red-600">
                    D{{ number_format($overstayStatistics['total_pending_payment'], 2) }}
                </div>
            </div>
        </div>
    @endif

    <!-- Data Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-blue-600 text-white border-b border-gray-200">
                    <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-blue-700" wire:click="sortOverstayDevicesBy('invoice_number')">
                        <div class="flex items-center gap-2">
                            Invoice Number
                            @if($overstayFilters['sort_by'] === 'invoice_number')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    @if($overstayFilters['sort_direction'] === 'asc')
                                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-center font-semibold">Device ID</th>
                    <th class="px-4 py-3 text-center font-semibold">SAD/BOE</th>
                    <th class="px-4 py-3 text-left font-semibold">Destination</th>
                    <th class="px-4 py-3 text-left font-semibold">Allocation Point</th>
                    <th class="px-4 py-3 text-center font-semibold cursor-pointer hover:bg-blue-700" wire:click="sortOverstayDevicesBy('overstay_days')">
                        <div class="flex items-center justify-center gap-2">
                            Days
                            @if($overstayFilters['sort_by'] === 'overstay_days')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    @if($overstayFilters['sort_direction'] === 'asc')
                                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-right font-semibold cursor-pointer hover:bg-blue-700" wire:click="sortOverstayDevicesBy('total_amount')">
                        <div class="flex items-center justify-end gap-2">
                            Amount (GMD)
                            @if($overstayFilters['sort_by'] === 'total_amount')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    @if($overstayFilters['sort_direction'] === 'asc')
                                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                    <th class="px-4 py-3 text-center font-semibold">Invoice Date</th>
                    <th class="px-4 py-3 text-center font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filteredOverstayDevices as $invoice)
                    <tr class="@if($loop->odd) bg-white @else bg-gray-50 @endif border-b border-gray-200 hover:bg-gray-100 transition">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $invoice->reference_number }}</td>
                        <td class="px-4 py-3 text-center">{{ $invoice->deviceRetrieval?->device?->device_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $invoice->sad_boe ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->deviceRetrieval?->destination?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->deviceRetrieval?->allocationPoint?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($invoice->overstay_days > 30) bg-red-100 text-red-800 @elseif($invoice->overstay_days > 7) bg-orange-100 text-orange-800 @else bg-yellow-100 text-yellow-800 @endif">
                                {{ $invoice->overstay_days }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">D{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($invoice->status === 'PP') bg-red-100 text-red-800 @elseif($invoice->status === 'PD') bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">
                                {{ $invoice->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $invoice->reference_date ? $invoice->reference_date->format('Y-m-d') : 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <!-- View PDF Button -->
                                <a href="{{ route('invoices.pdf', $invoice) }}" 
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </a>
                                <!-- Download PDF Button -->
                                <a href="{{ route('invoices.pdf', $invoice) }}" 
                                   download="invoice_{{ $invoice->reference_number }}.pdf"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                            No overstay devices found matching the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($filteredOverstayDevices && $filteredOverstayDevices->hasPages())
        <div class="mt-4 flex items-center justify-between">
            <p class="text-sm text-gray-600">
                Showing 
                <span class="font-medium">{{ $filteredOverstayDevices->firstItem() }}</span> 
                to 
                <span class="font-medium">{{ $filteredOverstayDevices->lastItem() }}</span>
                of 
                <span class="font-medium">{{ $filteredOverstayDevices->total() }}</span>
                results
            </p>

            <div class="flex gap-2">
                @if($filteredOverstayDevices->onFirstPage())
                    <button class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed" disabled>← Previous</button>
                @else
                    <button 
                        wire:click="$set('overstayFilters.page', {{ $filteredOverstayDevices->currentPage() - 1 }})"
                        class="px-3 py-2 text-sm text-blue-600 hover:text-blue-800 font-medium"
                    >
                        ← Previous
                    </button>
                @endif

                <!-- Page Numbers -->
                @foreach($filteredOverstayDevices->getUrlRange(1, $filteredOverstayDevices->lastPage()) as $page => $url)
                    @if($page == $filteredOverstayDevices->currentPage())
                        <button class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md font-medium">{{ $page }}</button>
                    @else
                        <button 
                            wire:click="$set('overstayFilters.page', {{ $page }})"
                            class="px-3 py-2 text-sm text-gray-600 hover:text-blue-600 font-medium"
                        >
                            {{ $page }}
                        </button>
                    @endif
                @endforeach

                @if($filteredOverstayDevices->hasMorePages())
                    <button 
                        wire:click="$set('overstayFilters.page', {{ $filteredOverstayDevices->currentPage() + 1 }})"
                        class="px-3 py-2 text-sm text-blue-600 hover:text-blue-800 font-medium"
                    >
                        Next →
                    </button>
                @else
                    <button class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed" disabled>Next →</button>
                @endif
            </div>
        </div>
    @endif
</div>
