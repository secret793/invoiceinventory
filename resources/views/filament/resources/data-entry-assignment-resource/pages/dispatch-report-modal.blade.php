<div class="space-y-4" wire:key="dispatch-report-{{ now() }}">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header Section -->
        <div class="px-6 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Dispatch Report</span>
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $assignment->title ?? 'N/A' }}</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $this->dispatchLogs->total() }} Records
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Filters -->
        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50/30">
            <div class="space-y-3">
                <!-- All Filters in One Row -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- Device ID Search -->
                    <div>
                        <label for="device_id" class="block text-xs font-medium text-gray-600 mb-1">Device ID</label>
                        <input type="text" id="device_id" wire:model.live="filters.device_id"
                               placeholder="Device..."
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
                        <input type="date" id="start_date" wire:model.live="filters.start_date"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
                        <input type="date" id="end_date" wire:model.live="filters.end_date"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Time From -->
                    <div>
                        <label for="start_time" class="block text-xs font-medium text-gray-600 mb-1">Time From</label>
                        <input type="time" id="start_time" wire:model.live="filters.start_time"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Time To -->
                    <div>
                        <label for="end_time" class="block text-xs font-medium text-gray-600 mb-1">Time To</label>
                        <input type="time" id="end_time" wire:model.live="filters.end_time"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>
                </div>

                <!-- Action Buttons Row -->
                <div class="flex justify-end space-x-2">
                    <button type="button" wire:click="resetFilters"
                            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-all duration-200 shadow-sm">
                        Reset
                    </button>
                    <button type="button" wire:click="applyFilters"
                            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-all duration-200 shadow-sm">
                       Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto bg-white">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80">
                        @php
                            $columns = [
                                'device_id' => 'Device ID',
                                'dispatched_at' => 'Dispatched At',
                                'dispatcher' => 'Dispatched By',
                                'boe' => 'BOE #',
                                'vehicle_number' => 'Vehicle #',
                                'regime' => 'Regime',
                                'route' => 'Route',
                                'destination' => 'Destination',
                            ];
                        @endphp
                        @foreach($columns as $col => $label)
                            <th class="px-6 py-5 text-left">
                                <button type="button" wire:click="sortBy('{{ $col }}')"
                                   class="group inline-flex items-center space-x-1 text-xs font-semibold text-gray-600 uppercase tracking-wider hover:text-gray-900 transition-colors duration-200">
                                    <span>{{ $label }}</span>
                                    <span class="flex flex-col ml-1">
                                        @if(($filters['sort_by'] ?? 'dispatched_at') === $col)
                                            @if(($filters['sort_direction'] ?? 'desc') === 'asc')
                                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="w-3 h-3 text-gray-400 group-hover:text-gray-600 transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @endif
                                    </span>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->dispatchLogs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $log->device->device_id ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->dispatched_at?->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $log->dispatched_at?->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->dispatcher->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $log->details['boe'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->details['vehicle_number'] ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $log->device->confirmedAffixed->regime ?? $log->details['regime'] ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $log->device->confirmedAffixed->route->name ?? $log->device->confirmedAffixed->longRoute->name ?? $log->details['route'] ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->details['destination'] ?? 'N/A' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center space-y-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <div class="text-sm font-medium text-gray-900">No dispatch logs found</div>
                                    <div class="text-xs text-gray-500">Try adjusting your search filters</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->dispatchLogs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $this->dispatchLogs->firstItem() ?? 0 }} to {{ $this->dispatchLogs->lastItem() ?? 0 }} of {{ $this->dispatchLogs->total() }} results
                    </div>
                    <div>
                        {{ $this->dispatchLogs->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
