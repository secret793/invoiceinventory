<div>
    <x-filament-panels::page>
        {{-- Header Section --}}
        <div class="mb-6">
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage, filter, and export overstay receipts records</p>
        </div>

        {{-- Filter Bar Section --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 
                    p-6 rounded-lg shadow-md mb-6 border border-blue-100 dark:border-gray-700">
            <div class="space-y-4">
                {{-- First Row: Search and Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-3">
                    {{-- Reference Search --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reference/SAD
                        </label>
                        <input 
                            type="text" 
                            wire:model.debounce.500ms="referenceSearch"
                            placeholder="Search..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition placeholder-gray-400 dark:placeholder-gray-500"
                        />
                    </div>

                    {{-- Destination Filter (Select Dropdown) --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Destination
                        </label>
                        <select 
                            wire:model.live="destinationFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition"
                        >
                            <option value="">All Destinations</option>
                            @foreach($availableDestinations as $dest)
                                <option value="{{ $dest }}">{{ $dest }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Regime Filter (Searchable Dropdown) --}}
                    <div class="lg:col-span-1 relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Regime
                        </label>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="regimeSearch"
                            placeholder="Search regimes..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition placeholder-gray-400 dark:placeholder-gray-500"
                        />
                        
                        {{-- Dropdown Results --}}
                        @if($regimeSearch && count($filteredRegimes) > 0)
                            <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg z-20 max-h-48 overflow-y-auto">
                                @foreach($filteredRegimes as $id => $name)
                                    <button type="button"
                                            wire:click="selectRegime('{{ $id }}')"
                                            class="w-full text-left px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-600 text-gray-900 dark:text-white transition-colors">
                                        {{ $name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        
                        {{-- Selected Regime Display --}}
                        @if($regimeFilter && isset($availableRegimes[$regimeFilter]))
                            <div class="mt-2 flex items-center gap-2 text-xs">
                                <span class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
                                    {{ $availableRegimes[$regimeFilter] }}
                                </span>
                                <button type="button"
                                        wire:click="clearRegime()"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 font-bold">
                                    ✕
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Allocation Point Filter (Searchable Dropdown) --}}
                    <div class="lg:col-span-1 relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Allocation Point
                        </label>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="allocationPointSearch"
                            placeholder="Search points..."
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
                                            class="w-full text-left px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-600 text-gray-900 dark:text-white transition-colors">
                                        {{ $name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        
                        {{-- Selected Allocation Point Display --}}
                        @if($allocationPointFilter && isset($availableAllocationPoints[$allocationPointFilter]))
                            <div class="mt-2 flex items-center gap-2 text-xs">
                                <span class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
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

                    {{-- Status Filter --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Status
                        </label>
                        <select 
                            wire:model.live="statusFilter"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition"
                        >
                            <option value="">All Status</option>
                            <option value="PP">Pending Payment (PP)</option>
                            <option value="PD">Paid (PD)</option>
                            <option value="WAIVED">Waived</option>
                            <option value="RJ">Rejected (RJ)</option>
                        </select>
                    </div>

                    {{-- Start Date --}}
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

                    {{-- End Date --}}
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

                    {{-- Sort By --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Sort By
                        </label>
                        <select 
                            wire:model.live="sortBy"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                   text-gray-900 dark:text-white dark:bg-gray-700
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   transition"
                        >
                            <option value="created_at">Newest First</option>
                            <option value="reference_date">Reference Date</option>
                            <option value="total_amount">Total Amount</option>
                            <option value="overstay_days">Overstay Days</option>
                        </select>
                    </div>
                </div>

                {{-- Second Row: Buttons --}}
                <div class="flex flex-wrap gap-3 pt-2">
                    {{-- Calculate Active Filters --}}
                    @php
                        $activeFilters = 0;
                        if (!empty($referenceSearch)) $activeFilters++;
                        if (!empty($regimeFilter)) $activeFilters++;
                        if (!empty($allocationPointFilter)) $activeFilters++;
                        if (!empty($statusFilter)) $activeFilters++;
                        if (!empty($startDate)) $activeFilters++;
                        if (!empty($endDate)) $activeFilters++;
                    @endphp

                    {{-- Apply Filters Button (Always visible) --}}
                    <button 
                        wire:click="applyFilters"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 
                               text-white rounded-lg font-medium transition-colors duration-200
                               flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Apply Filters
                    </button>

                    {{-- Reset Button (Always visible) --}}
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

                    {{-- Export Excel Button (Always visible) --}}
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                {{-- Total Records Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Invoices</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ $statistics['total_records'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Amount Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Amount</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ number_format($statistics['total_amount'] ?? 0, 0) }}D
                            </p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Paid Count Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Paid (PD)</p>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-300 mt-2">
                                {{ $statistics['paid_count'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pending Count Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Pending (PP)</p>
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-300 mt-2">
                                {{ $statistics['pending_count'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Waived Count Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Waived</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-300 mt-2">
                                {{ $statistics['waived_count'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 2.697m8.368 11.192a7 7 0 11-9.922-9.922m5.854 5.853a3 3 0 11-4.243-4.243m4.243 4.243L9.172 9.172" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Data Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Ref #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">SAD/BOE</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Destination</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Device</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Regime</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Agent</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Days</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Amount (D)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $record->reference_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->sad_boe ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->deviceRetrieval?->destination?->name ?? $record->destination ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->device_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->regime?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->agent ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">
                                @if($record->overstay_days > 0)
                                    <span class="inline-block px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-xs font-semibold">
                                        {{ $record->overstay_days }}
                                    </span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">{{ number_format($record->total_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($record->status === 'PD')
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">🟢 Paid</span>
                                @elseif($record->status === 'PP')
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">🟡 Pending</span>
                                @elseif($record->status === 'WAIVED')
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">🔵 Waived</span>
                                @elseif($record->status === 'RJ')
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">🔴 Rejected</span>
                                @else
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">{{ $record->status ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->reference_date?->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <p class="text-sm">No invoices found matching your filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $records->links() }}
        </div>
    </x-filament-panels::page>
</div>