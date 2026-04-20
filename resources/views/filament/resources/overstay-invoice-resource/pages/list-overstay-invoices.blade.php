<div x-data @open-export-url.window="window.open($event.detail.url, '_blank')">
    <x-filament-panels::page>
        {{-- Header Section --}}
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Overstay Receipts</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage, filter, and export overstay transactions</p>
        </div>

        {{-- Filter Bar Section --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 
                    p-6 rounded-lg shadow-md mb-6 border border-blue-100 dark:border-gray-700">
            <div class="space-y-4">
                {{-- First Row: Search and Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    {{-- Receipt/SAD Search --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Receipt/SAD
                        </label>
                        <input 
                            type="text" 
                            wire:model.debounce.500ms="receiptSearch"
                            placeholder="Search..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition placeholder-gray-400 dark:placeholder-gray-500"
                        />
                    </div>

                    {{-- Destination Search (Searchable Dropdown) --}}
                    <div class="lg:col-span-1 relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Destination
                        </label>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="destinationSearch"
                            placeholder="Search destinations..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition placeholder-gray-400 dark:placeholder-gray-500"
                        />
                        
                        {{-- Dropdown Results --}}
                        @if($destinationSearch && count($filteredDestinations) > 0)
                            <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg z-20 max-h-48 overflow-y-auto">
                                @foreach($filteredDestinations as $id => $name)
                                    <button type="button"
                                            wire:click="selectDestination('{{ $id }}')"
                                            class="w-full text-left px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-600 text-gray-900 dark:text-white transition-colors">
                                        {{ $name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        
                        {{-- Selected Destination Display --}}
                        @if($destinationFilter && isset($availableDestinations[$destinationFilter]))
                            <div class="mt-2 flex items-center gap-2 text-xs">
                                <span class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
                                    {{ $availableDestinations[$destinationFilter] }}
                                </span>
                                <button type="button"
                                        wire:click="clearDestination()"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 font-bold">
                                    ✕
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Allocation Point Search (Searchable Dropdown) --}}
                    <div class="lg:col-span-1 relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Allocation Point
                        </label>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="allocationPointSearch"
                            placeholder="Search AP..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition placeholder-gray-400 dark:placeholder-gray-500"
                        />
                        
                        {{-- Dropdown Results --}}
                        @if($allocationPointSearch && count($filteredAllocationPoints) > 0)
                            <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg z-20 max-h-48 overflow-y-auto">
                                @foreach($filteredAllocationPoints as $id => $name)
                                    <button type="button"
                                            wire:click="selectAllocationPoint('{{ $id }}')"
                                            class="w-full text-left px-4 py-2 hover:bg-green-50 dark:hover:bg-gray-600 text-gray-900 dark:text-white transition-colors">
                                        {{ $name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        
                        {{-- Selected AP Display --}}
                        @if($allocationPointFilter && isset($availableAllocationPoints[$allocationPointFilter]))
                            <div class="mt-2 flex items-center gap-2 text-xs">
                                <span class="inline-block bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-1 rounded">
                                    {{ $availableAllocationPoints[$allocationPointFilter] }}
                                </span>
                                <button type="button"
                                        wire:click="clearAllocationPoint()"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 font-bold">
                                    ✕
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- From Date --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            From Date
                        </label>
                        <input 
                            type="date" 
                            wire:model.live="startDate"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition"
                        />
                    </div>

                    {{-- To Date --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            To Date
                        </label>
                        <input 
                            type="date" 
                            wire:model.live="endDate"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition"
                        />
                    </div>
                </div>

                {{-- Second Row: Buttons --}}
                <div class="flex flex-wrap gap-3 pt-2">
                    <button 
                        wire:click="resetFilters"
                        class="px-4 py-2 bg-gray-400 hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-700 
                               text-white rounded-lg font-medium transition-colors duration-200
                               flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reset
                    </button>

                    <button 
                        wire:click="exportRecords"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 
                               text-white rounded-lg font-medium transition-colors duration-200
                               flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Excel
                    </button>

                    {{-- Active Filter Count Indicator --}}
                    @php
                        $activeFilters = 0;
                        if (!empty($receiptSearch)) $activeFilters++;
                        if (!empty($destinationFilter)) $activeFilters++;
                        if (!empty($allocationPointFilter)) $activeFilters++;
                        if (!empty($startDate)) $activeFilters++;
                        if (!empty($endDate)) $activeFilters++;
                    @endphp

                    @if($activeFilters > 0)
                        <div class="flex items-center gap-2 ml-auto text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3h2a1 1 0 011 1v3h-2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7H1V7h2V3zm0 5v7a1 1 0 001 1h10a1 1 0 001-1v-7H3z"/>
                            </svg>
                            <span class="font-medium">{{ $activeFilters }} filter{{ $activeFilters !== 1 ? 's' : '' }} active</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Statistics Bar Section (Only show when date range is set) --}}
        @if($hasDateFilter && !empty($statistics))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- Total Invoices Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Overstay Transactions</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ $statistics['total_invoices'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Trucks Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Number of Trucks</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ $statistics['total_trucks'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-orange-100 dark:bg-orange-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0015.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Amount Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Amount (GMD)</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                D {{ number_format($statistics['total_amount'] ?? 0, 2) }}
                            </p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Data Table Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Receipt No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">SAD</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Destination</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">A.P</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Device</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Days</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total (GMD)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Generated By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($records as $record)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-2 py-1 rounded text-sm font-medium">
                                        {{ $record->reference_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->sad_boe ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->deviceRetrieval?->destination?->name ?? $record->destination ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->deviceRetrieval?->allocationPoint?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->deviceRetrieval?->vehicle_number ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->deviceRetrieval?->device_id ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $record->overstay_days ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                    D {{ number_format($record->total_amount ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->generatedByUser?->name ?? $record->created_by ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'PP' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                                            'PD' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                            'WAIVED' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                        ];
                                        $color = $statusColors[$record->status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
                                    @endphp
                                    <span class="inline-block {{ $color }} px-2 py-1 rounded text-xs font-medium">
                                        {{ $record->status === 'PP' ? 'Pending' : ($record->status === 'PD' ? 'Paid' : 'Waived') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $record->created_at?->format('Y-m-d H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="font-medium">No overstay receipts found</p>
                                    <p class="text-sm mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($records->hasPages())
                <div class="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </x-filament-panels::page>
</div>
