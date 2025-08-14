<div class="space-y-4" wire:key="device-retrieval-report-{{ now() }}">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header Section -->
        <div class="px-6 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11H7a2 2 0 01-2-2V7a2 2 0 012-2h2m0 4h10a2 2 0 002-2V7a2 2 0 00-2-2H9m0 4v6a2 2 0 002 2h2a2 2 0 002-2v-2M7 7h2m6 0h2"/>
                        </svg>
                        <span>Device Retrieval Report</span>
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Device retrieval status and history</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $this->getDeviceRetrievalLogsProperty()->total() }} Records
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Filters -->
        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50/30">
            <div class="space-y-3">
                <!-- All Filters in One Row -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                        <input type="text"
                               id="search"
                               wire:model.live.debounce.300ms="filters.search"
                               placeholder="Search device ID, BOE, vehicle..."
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Device ID -->
                    <div>
                        <label for="device_id" class="block text-xs font-medium text-gray-600 mb-1">Device ID</label>
                        <input type="text"
                               id="device_id"
                               wire:model.live="filters.device_id"
                               placeholder="Device ID"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- BOE -->
                    <div>
                        <label for="boe" class="block text-xs font-medium text-gray-600 mb-1">BOE</label>
                        <input type="text"
                               id="boe"
                               wire:model.live="filters.boe"
                               placeholder="BOE"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Vehicle Number -->
                    <div>
                        <label for="vehicle_number" class="block text-xs font-medium text-gray-600 mb-1">Vehicle Number</label>
                        <input type="text"
                               id="vehicle_number"
                               wire:model.live="filters.vehicle_number"
                               placeholder="Vehicle #"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Allocation Point -->
                    <div>
                        <label for="allocation_point_id" class="block text-xs font-medium text-gray-600 mb-1">Allocation Point</label>
                        <select id="allocation_point_id"
                                wire:model.live="filters.allocation_point_id"
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                            <option value="">All Allocation Points</option>
                            @foreach(\App\Models\AllocationPoint::withoutGlobalScopes()->orderBy('name')->get() as $allocationPoint)
                                <option value="{{ $allocationPoint->id }}">{{ $allocationPoint->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Second Row with Date/Time Filters -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
                        <input type="date"
                               id="start_date"
                               wire:model.live="filters.start_date"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
                        <input type="date"
                               id="end_date"
                               wire:model.live="filters.end_date"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Start Time -->
                    <div>
                        <label for="start_time" class="block text-xs font-medium text-gray-600 mb-1">Start Time</label>
                        <input type="time"
                               id="start_time"
                               wire:model.live="filters.start_time"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- End Time -->
                    <div>
                        <label for="end_time" class="block text-xs font-medium text-gray-600 mb-1">End Time</label>
                        <input type="time"
                               id="end_time"
                               wire:model.live="filters.end_time"
                               class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                    </div>

                    <!-- Retrieval Status -->
                    <div>
                        <label for="retrieval_status" class="block text-xs font-medium text-gray-600 mb-1">Retrieval Status</label>
                        <select id="retrieval_status"
                                wire:model.live="filters.retrieval_status"
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                            <option value="">All Status</option>
                            <option value="NOT_RETRIEVED">Not Retrieved</option>
                            <option value="RETRIEVED">Retrieved</option>
                            <option value="RETURNED">Returned</option>
                        </select>
                    </div>

                    <!-- Action Type -->
                    <div>
                        <label for="action_type" class="block text-xs font-medium text-gray-600 mb-1">Action Type</label>
                        <select id="action_type"
                                wire:model.live="filters.action_type"
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200">
                            <option value="">All Actions</option>
                            <option value="RETRIEVED">Retrieved</option>
                            <option value="RETURNED">Returned</option>
                        </select>
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
                                'boe' => 'BOE/SAD',
                                'vehicle_number' => 'Vehicle #',
                                'allocation_point_id' => 'Allocation Point',
                                'retrieval_status' => 'Status',
                                'action_type' => 'Action',
                                'retrieval_date' => 'Retrieval Date',
                                'retrieved_by' => 'Retrieved By',
                                'overstay_days' => 'Overstay Days',
                                'overstay_amount' => 'Overstay Amount',
                            ];
                        @endphp
                        @foreach($columns as $col => $label)
                            <th class="px-6 py-5 text-left">
                                <button type="button" 
                                        wire:click="sortBy('{{ $col }}')"
                                        class="flex items-center space-x-1 text-xs font-semibold text-gray-600 uppercase tracking-wider hover:text-gray-900 transition-colors duration-150">
                                    <span>{{ $label }}</span>
                                    @php
                                        $sortBy = $this->filters['sort_by'] ?? 'created_at';
                                        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
                                    @endphp
                                    @if($sortBy === $col)
                                        @if($sortDirection === 'asc')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"/>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->getDeviceRetrievalLogsProperty() as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $log->device->device_id ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $log->boe ?: 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->vehicle_number ?: 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->allocationPoint ? $log->allocationPoint->name : 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $log->retrieval_status === 'RETRIEVED' ? 'bg-green-100 text-green-800' :
                                       ($log->retrieval_status === 'NOT_RETRIEVED' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-blue-100 text-blue-800') }}">
                                    {{ $log->retrieval_status }}
                                </span>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $log->action_type === 'RETRIEVED' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $log->action_type }}
                                </span>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->retrieval_date ? $log->retrieval_date->format('M d, Y') : 'N/A' }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $log->retrieval_date ? $log->retrieval_date->format('h:i A') : '' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->retrievedBy->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="text-sm {{ $log->overstay_days > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                    {{ $log->overstay_days ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="text-sm {{ $log->overstay_amount > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                    GMD {{ number_format($log->overstay_amount ?? 0, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center space-y-3">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <div class="text-sm font-medium text-gray-900">No device retrieval logs found</div>
                                    <div class="text-xs text-gray-500">Try adjusting your search filters</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->getDeviceRetrievalLogsProperty()->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $this->getDeviceRetrievalLogsProperty()->firstItem() ?? 0 }} to {{ $this->getDeviceRetrievalLogsProperty()->lastItem() ?? 0 }} of {{ $this->getDeviceRetrievalLogsProperty()->total() }} results
                    </div>
                    <div>
                        {{ $this->getDeviceRetrievalLogsProperty()->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
