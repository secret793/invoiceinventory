<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status Counts --}}
        <div class="grid grid-cols-4 gap-4">
            @foreach ($status_counts as $status => $count)
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">{{ $status }}</div>
                    <div class="text-2xl font-bold">{{ $count }}</div>
                </div>
            @endforeach
        </div>

        {{-- Status Tabs --}}
        <div class="bg-white rounded-lg shadow">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="#" class="border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">ONLINE</a>
                    <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">OFFLINE</a>
                    <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">DAMAGED</a>
                    <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">FIXED</a>
                    <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">LOST</a>
                    <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">RECEIVED</a>
                </nav>
            </div>
        </div>

        {{-- Remove the table reference --}}
        {{-- <div>{{ $this->table }}</div> --}}
    </div>
</x-filament-panels::page>