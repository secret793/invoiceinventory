<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Other Items Statistics</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">OK</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Damaged</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lost</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Distributed</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Allocated</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assigned</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Remaining</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($this->getStats() as $description => $stats)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $description }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['OK'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Damaged'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Lost'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Total'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Distributed'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Allocated'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Assigned'] }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $stats['Remaining'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
