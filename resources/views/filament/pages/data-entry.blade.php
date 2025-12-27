<x-filament::page>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">{{ $allocationPoint->name }}</h2>
            <x-filament::button>
                Assign New Data
            </x-filament::button>
        </div>

        <!-- Example Data Table -->
        <x-filament::table>
            <x-slot name="header">
                <x-filament::table.header>
                    <x-filament::table.cell>Name</x-filament::table.cell>
                    <x-filament::table.cell>Status</x-filament::table.cell>
                    <x-filament::table.cell>Assigned Agent</x-filament::table.cell>
                </x-filament::table.header>
            </x-slot>
            <x-slot name="body">
                <!-- Loop through records -->
                @foreach ($allocationPoint->assignments as $assignment)
                    <x-filament::table.row>
                        <x-filament::table.cell>{{ $assignment->name }}</x-filament::table.cell>
                        <x-filament::table.cell>{{ $assignment->status }}</x-filament::table.cell>
                        <x-filament::table.cell>{{ $assignment->agent->name }}</x-filament::table.cell>
                    </x-filament::table.row>
                @endforeach
            </x-slot>
        </x-filament::table>
    </div>
</x-filament::page>
