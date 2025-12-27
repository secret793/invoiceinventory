<div class="p-6">
    <!-- Filter Panel -->
    <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 p-4 rounded-lg border border-blue-200 dark:border-gray-700">
        <!-- Row 1: Receipt Search, Destination, Sort -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Receipt Number Search -->
            <div>
                <label for="receipt_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Receipt/SAD Search</label>
                <input type="text" 
                       id="receipt_search"
                       wire:model.debounce.500ms="receiptFilters.receipt_number"
                       placeholder="Search receipt # or SAD..."
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            </div>

            <!-- Destination Filter (Searchable) -->
            <div class="relative">
                <label for="destination_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Destination (Search)</label>
                <input type="text" 
                       id="destination_search"
                       wire:model.debounce.300ms="destinationSearch"
                       placeholder="Search destinations..."
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all" />
                
                <!-- Dropdown Results -->
                @if($destinationSearch && count($filteredDestinations) > 0)
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                        @foreach($filteredDestinations as $id => $name)
                            <button type="button"
                                    wire:click="selectDestination('{{ $id }}')"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                {{ $name }}
                            </button>
                        @endforeach
                    </div>
                @endif
                
                <!-- Selected Destination Display -->
                @if($receiptFilters['destination_id'] && isset($receiptDestinations[$receiptFilters['destination_id']]))
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
                            {{ $receiptDestinations[$receiptFilters['destination_id']] }}
                        </span>
                        <button type="button"
                                wire:click="clearDestination"
                                class="text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            ✕
                        </button>
                    </div>
                @endif
            </div>

            <!-- Sort By -->
            <div>
                <label for="sort_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
                <select wire:model.live="receiptFilters.sort_by"
                        id="sort_by"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                    <option value="date">Date (Latest)</option>
                    <option value="receipt_number">Receipt Number</option>
                    <option value="total_charge_gmd">Total Amount</option>
                    <option value="moving_trucks">Trucks Count</option>
                </select>
            </div>
        </div>

        <!-- Row 2: Date/Time Range -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label for="start_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" 
                       id="start_date"
                       wire:model.live="receiptFilters.start_date"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            </div>
            <div>
                <label for="start_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                <input type="time" 
                       id="start_time"
                       wire:model.live="receiptFilters.start_time"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            </div>
            <div>
                <label for="end_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" 
                       id="end_date"
                       wire:model.live="receiptFilters.end_date"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            </div>
            <div>
                <label for="end_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                <input type="time" 
                       id="end_time"
                       wire:model.live="receiptFilters.end_time"
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            </div>
        </div>

        <!-- Row 3: Action Buttons -->
        <div class="flex flex-wrap gap-2 justify-end">
            <button type="button"
                    wire:click.prevent="resetReceiptFilters"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                🔄 Reset
            </button>
            <button type="button"
                    wire:click.prevent="applyReceiptFilters"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                🔍 Apply Filters
            </button>
            <button type="button"
                    wire:click.prevent="exportReceipts"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                📥 Export to Excel
            </button>
        </div>
    </div>

    <!-- Statistics Panel (Conditional) -->
    @if($receiptStatistics)
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 p-4 rounded-lg">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">📋 Receipts</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $receiptStatistics['total_receipts'] }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800 p-4 rounded-lg">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">🚚 Trucks</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $receiptStatistics['total_trucks'] }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-4 rounded-lg">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">💰 Total Amount</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">D {{ number_format($receiptStatistics['total_amount'], 2) }}</p>
            </div>
        </div>
    @endif

    <!-- Data Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    @php
                        $columns = [
                            'receipt_number' => 'Receipt Number',
                            'sad_number' => 'SAD/T1',
                            'route' => 'Route (Short)',
                            'long_route' => 'Route (Long)',
                            'destination' => 'Destination',
                            'date' => 'Date & Time',
                            'moving_trucks' => 'Trucks',
                            'used' => 'Available Usage',
                            'total_charge_gmd' => 'Total Charged (GMD)',
                            'actions' => 'Actions',
                        ];
                    @endphp
                    @foreach($columns as $col => $label)
                        @if($col === 'actions')
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</th>
                        @else
                            <th class="px-6 py-3 text-left">
                                <button type="button"
                                        wire:click.prevent="sortReceiptsBy('{{ $col }}')"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    <span>{{ $label }}</span>
                                    @if($receiptFilters['sort_by'] === $col)
                                        @if($receiptFilters['sort_direction'] === 'asc')
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M3.293 7.293a1 1 0 011.414 0L10 10.586l5.293-5.293a1 1 0 111.414 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414z" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M16.707 12.707a1 1 0 01-1.414 0L10 9.414l-5.293 5.293a1 1 0 01-1.414-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 010 1.414z" />
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:text-gray-500 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M3.293 7.293a1 1 0 011.414 0L10 10.586l5.293-5.293a1 1 0 111.414 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414z" />
                                        </svg>
                                    @endif
                                </button>
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($filteredReceipts as $receipt)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $receipt->receipt_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $receipt->sad_number ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $receipt->route?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $receipt->longRoute?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $receipt->destination?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $receipt->date ? $receipt->date->format('Y-m-d H:i') : 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-gray-900 dark:text-white">{{ $receipt->moving_trucks }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600 dark:text-gray-400">{{ $receipt->used }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-gray-900 dark:text-white">D {{ number_format($receipt->total_charge_gmd, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-2">
                                <!-- View PDF Button -->
                                <a href="{{ route('receipts.pdf', $receipt) }}" 
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </a>
                                <!-- Download PDF Button -->
                                <a href="{{ route('receipts.pdf', $receipt) }}" 
                                   download="receipt_{{ $receipt->receipt_number }}.pdf"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
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
                        <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No receipts found matching your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 dark:text-white">
        {{ $filteredReceipts->links() }}
    </div>
</div>
