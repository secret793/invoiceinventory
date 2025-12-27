<div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 rounded-lg border border-blue-100 dark:border-gray-700 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Receipt Search -->
        <div class="lg:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Receipt / SAD
            </label>
            <input 
                type="text"
                wire:model.debounce.500ms="receiptSearch"
                placeholder="Search receipt..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
        </div>

        <!-- Destination Filter -->
        <div class="lg:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Destination
            </label>
            <select 
                wire:model.live="destinationFilter"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            >
                <option value="">All Destinations</option>
                @foreach ($this->availableDestinations as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Start Date -->
        <div class="lg:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Start Date
            </label>
            <input 
                type="date"
                wire:model.live="startDate"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
        </div>

        <!-- End Date -->
        <div class="lg:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                End Date
            </label>
            <input 
                type="date"
                wire:model.live="endDate"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
        </div>

        <!-- Sort By -->
        <div class="lg:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Sort
            </label>
            <select 
                wire:model.live="sortBy"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            >
                <option value="created_at">Date (Newest)</option>
                <option value="receipt_number">Receipt #</option>
                <option value="amount">Amount</option>
            </select>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-4 flex flex-wrap gap-2">
        <button 
            wire:click="resetFilters"
            type="button"
            class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-sm font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Reset Filters
        </button>

        <button 
            wire:click="exportReceipts"
            type="button"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white text-sm font-semibold rounded-lg hover:from-green-600 hover:to-emerald-700 transition shadow-md"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8m0 8l-6-2m6 2l6-2"></path>
            </svg>
            Export Excel
        </button>

        <div class="flex-1"></div>

        <!-- Active Filters Count -->
        @php
            $activeFilters = 0;
            if (!empty($receiptSearch)) $activeFilters++;
            if (!empty($destinationFilter)) $activeFilters++;
            if (!empty($startDate)) $activeFilters++;
            if (!empty($endDate)) $activeFilters++;
        @endphp

        @if ($activeFilters > 0)
            <div class="inline-flex items-center px-3 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 text-sm rounded-lg font-medium">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L13 11.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 007 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                </svg>
                {{ $activeFilters }} {{ Str::plural('filter', $activeFilters) }} active
            </div>
        @endif
    </div>
</div>
