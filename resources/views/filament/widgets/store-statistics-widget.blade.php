{{-- resources/views/filament/widgets/store-statistics-widget.blade.php --}}
<div class="p-4 bg-white rounded-lg shadow">
    <h2 class="text-lg font-semibold">Store Statistics</h2>
    <ul>
        <li>Total Stores: {{ $this->getStatistics()['totalStores'] }}</li>
        <li>Active Stores: {{ $this->getStatistics()['activeStores'] }}</li>
        {{-- Add more statistics as needed --}}
    </ul>
</div>

