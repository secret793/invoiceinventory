<div>
    <x-filament-panels::page>
        {{-- Header Section --}}
        <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Generated Receipts</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage, filter, and export generated receipts</p>
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

                {{-- Destination Select Field --}}
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
                        <option value="">-- Select Destination --</option>
                        @forelse($availableDestinations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @empty
                            <option disabled>No destinations available</option>
                        @endforelse
                    </select>
                </div>

                {{-- Allocation Point Select Field --}}
                <div class="lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Allocation Point
                    </label>
                    <select 
                        wire:model.live="allocationPointFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                               text-gray-900 dark:text-white dark:bg-gray-700
                               focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               transition"
                    >
                        <option value="">-- Select Allocation Point --</option>
                        @forelse($availableAllocationPoints as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @empty
                            <option disabled>No allocation points available</option>
                        @endforelse
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
                        <option value="created_at">Date Created</option>
                        <option value="receipt_number">Receipt Number</option>
                        <option value="total_charge_gmd">Total Amount</option>
                    </select>
                </div>
            </div>

            {{-- Second Row: Buttons --}}
            <div class="flex flex-wrap gap-3 pt-2">
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
                    wire:click="exportReceipts"
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            {{-- Total Receipts Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Receipts</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ $statistics['total_receipts'] ?? 0 }}
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
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Trucks</p>
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
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Amount</p>
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

            {{-- Total Short Routes Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Short Routes</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ $statistics['total_short_routes'] ?? 0 }}
                        </p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L9.9 4.05a7 7 0 00-9.9 9.9zM9.9 4.05L4.05 9.9a7 7 0 009.9-9.9zM8.54 20a7 7 0 01-7.07-7.07h5.25a1.75 1.75 0 001.75-1.75V5.48A7 7 0 018.54 20z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Long Routes Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-pink-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Long Routes</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ $statistics['total_long_routes'] ?? 0 }}
                        </p>
                    </div>
                    <div class="bg-pink-100 dark:bg-pink-900 p-3 rounded-full">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L9.9 4.05a7 7 0 00-9.9 9.9zM9.9 4.05L4.05 9.9a7 7 0 009.9-9.9zM8.54 20a7 7 0 01-7.07-7.07h5.25a1.75 1.75 0 001.75-1.75V5.48A7 7 0 018.54 20z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Data Table Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Table Header --}}
                <thead class="bg-gray-50 dark:bg-gray-900 border-b-2 border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Receipt #
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            SAD
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Route
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Long Route
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            A.P
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Destination
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Trucks
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Amount (D)
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Usage
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Generated By
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Created
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($receipts as $receipt)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ $receipt->receipt_number }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $receipt->sad_number ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $receipt->route?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $receipt->longRoute?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $receipt->allocationPoint?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $receipt->destination?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white font-medium">
                                {{ $receipt->moving_trucks }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white font-semibold">
                                D {{ number_format($receipt->total_charge_gmd, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if(($receipt->getUsageBadgeColor()) === 'success')
                                        bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                    @elseif(($receipt->getUsageBadgeColor()) === 'warning')
                                        bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                    @else
                                        bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                    @endif
                                ">
                                    {{ $receipt->getUsageDisplay() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $receipt->generatedByUser?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $receipt->created_at->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-gray-600 dark:text-gray-400 font-medium">No receipts found</p>
                                    <p class="text-gray-500 dark:text-gray-500 text-sm mt-1">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($receipts->hasPages())
            <div class="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
</div>
