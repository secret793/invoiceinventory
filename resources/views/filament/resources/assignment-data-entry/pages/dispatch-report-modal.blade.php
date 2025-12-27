<div x-data="{
    startDate: null,
    endDate: null,
    search: '',
    
    get filteredLogs() {
        let logs = {{ Js::from($dispatchLogs) }};
        
        // Filter by date range
        if (this.startDate && this.endDate) {
            const start = new Date(this.startDate);
            const end = new Date(this.endDate);
            end.setHours(23, 59, 59); // Include the entire end day
            
            logs = logs.filter(log => {
                const logDate = new Date(log.dispatched_at);
                return logDate >= start && logDate <= end;
            });
        }
        
        // Filter by search term
        if (this.search) {
            const searchTerm = this.search.toLowerCase();
            logs = logs.filter(log => 
                (log.device?.device_id?.toLowerCase().includes(searchTerm)) ||
                (log.dispatcher?.name?.toLowerCase().includes(searchTerm)) ||
                (log.details?.boe?.toLowerCase().includes(searchTerm)) ||
                (log.details?.vehicle_number?.toLowerCase().includes(searchTerm)) ||
                (log.details?.destination?.toLowerCase().includes(searchTerm))
            );
        }
        
        return logs;
    }
}" class="space-y-4">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">
                        Dispatch Report - {{ $assignment->title ?? 'N/A' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span x-text="filteredLogs.length">{{ $dispatchLogs->count() }}</span> records found
                    </p>
                </div>
                <div class="flex space-x-2">
                    <div class="relative">
                        <input type="date" 
                               x-model="startDate" 
                               class="text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        <span class="mx-2 text-gray-400">to</span>
                        <input type="date" 
                               x-model="endDate" 
                               class="text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                    </div>
                    <div class="relative">
                        <input type="text" 
                               x-model="search" 
                               placeholder="Search..." 
                               class="pl-10 pr-4 py-2 text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispatched At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispatched By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BOE #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" x-show="filteredLogs.length > 0">
                    <template x-for="log in filteredLogs" :key="log.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="log.device?.device_id || 'N/A'"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="new Date(log.dispatched_at).toLocaleString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="log.dispatcher?.name || 'N/A'"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="log.details?.boe || 'N/A'"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="log.details?.vehicle_number || 'N/A'"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="log.details?.destination || 'N/A'"></td>
                        </tr>
                    </template>
                </tbody>
                <tbody x-show="filteredLogs.length === 0">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No dispatch logs found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
