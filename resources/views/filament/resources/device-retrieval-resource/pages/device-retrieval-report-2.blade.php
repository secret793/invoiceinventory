<div class="space-y-4" wire:key="device-retrieval-report-2-{{ now() }}">
    <div c                        <select wire:model.live="dateColumn" 
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                          
        <!-- Header Section -->
        <div class="px-6 py-6 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-orange-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Device Retrieval Report #2</span>
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Advanced device retrieval analytics</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                        {{ $this->getReport2DataProperty()->total() }} Records
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/30">
            <!-- Main Filters Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Retrieval Status -->

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Retrieval Status</label>
                    <select wire:model.live="report2Filters.retrieval_status" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Select Status</option>
                        <option value="ALL_STATUS">All Status</option>
                        <option value="RETRIEVED">Retrieved</option>
                        <option value="RETURNED">Returned</option>
                    </select>
                </div>
                
                <!-- Action Type 
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Action Type</label>
                    <select wire:model.live="report2Filters.action_type" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">All Actions</option>
                        <option value="RETRIEVED">Retrieved</option>
                        <option value="RETURNED">Returned</option>
                    </select>
                </div> -->

                <!-- Device ID Search -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Device ID</label>
                    <input type="text" 
                           wire:model.debounce.500ms="tempReport2Filters.device_id" 
                           placeholder="Search device ID..." 
                           class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- BOE Search -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">BOE</label>
                    <input type="text" 
                           wire:model.debounce.500ms="tempReport2Filters.boe" 
                           placeholder="Search BOE..." 
                           class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>

            <!-- Second Row for Date/Time Filters -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                    <!-- Date Filtering Controls -->
                    <div class="flex items-center mb-4 col-span-4">
                        <input type="checkbox" 
                               wire:model.live="useDateFiltering"
                               id="useDateFiltering"
                               class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                        <label for="useDateFiltering" class="ml-2 text-sm font-medium text-gray-700">
                            Enable Date Filtering
                        </label>
                    </div>

                    @if($this->useDateFiltering)
                    <!-- Date Column Selection -->
                    <div class="col-span-4 mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter by Date Type</label>
                        <select wire:model.live="dateColumn" 
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @if($this->report2Filters['retrieval_status'] === 'ALL_STATUS')
                                <option value="both">Both Dates</option>
                            @endif
                            <option value="retrieval_date">Retrieval Date</option>
                            <option value="returned_at">Return Date</option>
                        </select>
                    </div>                        <!-- Date Range -->
                        <div class="grid grid-cols-4 gap-3 col-span-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
                                <input type="date" 
                                    wire:model.defer="tempReport2Filters.start_date"
                                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
                                <input type="date" 
                                    wire:model.defer="tempReport2Filters.end_date"
                                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Start Time</label>
                                <input type="time" 
                                    wire:model.defer="tempReport2Filters.start_time"
                                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">End Time</label>
                                <input type="time" 
                                    wire:model.defer="tempReport2Filters.end_time"
                                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                        </div>
                    @endif
                </div>

            <!-- Filter Control Buttons -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-lg p-6 my-6 shadow-lg">
                <div class="flex items-center justify-center space-x-6">
                    <!-- Apply Filters Button (GREEN) -->
                    <button 
                        type="button"
                        wire:click.prevent="applyReport2Filters" 
                        wire:loading.attr="disabled" 
                        onclick="event.stopPropagation();"
                        class="inline-flex items-center px-8 py-4 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-black font-bold text-lg rounded-lg shadow-xl hover:shadow-2xl transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-300 disabled:transform-none disabled:hover:scale-100 border-2 border-green-800"
                    >
                        <svg class="w-6 h-6 mr-3 text-black" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                        </svg>
                        <span wire:loading.remove wire:target="applyReport2Filters">🔍 Apply Filters</span>
                        <span wire:loading wire:target="applyReport2Filters">⏳ Applying...</span>
                    </button>

                    <!-- Reset Filters Button (RED) -->
                    <button 
                        type="button"
                        wire:click.prevent="resetReport2Filters" 
                        wire:loading.attr="disabled" 
                        onclick="event.stopPropagation();"
                        class="inline-flex items-center px-8 py-4 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-black font-bold text-lg rounded-lg shadow-xl hover:shadow-2xl transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-red-300 disabled:transform-none disabled:hover:scale-100 border-2 border-red-800"
                    >
                        <svg class="w-6 h-6 mr-3 text-black" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                        </svg>
                        <span wire:loading.remove wire:target="resetReport2Filters">🔄 Reset All</span>
                        <span wire:loading wire:target="resetReport2Filters">⏳ Resetting...</span>
                    </button>
                </div>
                
                <!-- Instruction Text -->
                <div class="text-center mt-4">
                    <p class="text-sm text-amber-800 font-medium">
                        � <strong>Important:</strong> After setting your date/time filters above, click "Apply Filters" to search the data if you dont see the result
                    </p>
                </div>
            </div>

            <!-- Filter Help Text -->
            <div class="mt-3 text-xs text-gray-600 bg-blue-50 rounded p-2">
                <div class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong>Filtering Tips:</strong>
                        <ul class="mt-1 space-y-1">
                            <li>• <strong>Enable Date Filtering</strong> to filter results by date</li>
                            <li>• <strong>Date Type</strong> determines which date to use for filtering (Created Date or Updated Date)</li>
                            <li>• Click <strong>"Apply Filters"</strong> to search - typing won't trigger automatic filtering</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BOE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regime</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retrieval Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retrieved By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retrieval Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overstay Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overstay Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->getReport2DataProperty() as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $record->device_full_id ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->boe ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->vehicle_number ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($record->longRoute)
                                    <span class="font-semibold text-amber-700">Long Route: {{ $record->longRoute->name }}</span>
                                @elseif($record->route)
                                    {{ $record->route->name }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->regime ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->destination ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ match($record->retrieval_status) {
                                        'ALL_STATUS' => 'bg-amber-100 text-amber-800',
                                        'RETRIEVED' => 'bg-blue-100 text-blue-800',
                                        'RETURNED' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } }}">
                                    {{ match($record->retrieval_status) {
                                        'ALL_STATUS' => 'All status',
                                        'RETRIEVED' => 'Retrieved',
                                        'RETURNED' => 'Returned',
                                        default => 'N/A'
                                    } }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $record->action_type === 'RETRIEVED' ? 'bg-amber-100 text-amber-800' : 
                                       ($record->action_type === 'RETURNED' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $record->action_type ?: 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->retrievedBy?->name ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->returnedBy?->name ?: 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $record->retrieval_date ? $record->retrieval_date->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $record->returned_at ? $record->returned_at->format('Y-m-d H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->overstay_days ?: '0' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">GMD {{ number_format($record->overstay_amount ?: 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-6 py-12 text-center">
                                <div class="text-sm text-gray-500">No data found for Report #2</div>
                                <div class="text-xs text-gray-400 mt-1">Try adjusting your filters or check if there are any retrieved/returned devices.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
