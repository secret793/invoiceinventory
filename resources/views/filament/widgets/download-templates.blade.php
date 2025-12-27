<x-filament::widget>
    <x-filament::card>
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-medium">Template for DAMAGED devices</h2>
                <a href="{{ url('templates/damaged_devices_template.xlsx') }}" class="text-primary-600 hover:underline">Download</a>
            </div>
            <div>
                <h2 class="text-lg font-medium">Template for all OTHER device status</h2>
                <a href="{{ url('templates/other_devices_template.xlsx') }}" class="text-primary-600 hover:underline">Download</a>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
