<x-filament::page>
    <div class="space-y-6">
        {{-- Header --}}
        <h1 class="text-xl font-bold text-gray-700">Create Dispatch Assignment</h1>

        <form action="{{ route('dispatch.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="text"
                           value="{{ now()->format('Y-m-d H:i:s') }}"
                           disabled
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                </div>
                {{-- Device Serial --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Device Serial</label>
                    <input type="text" name="device_id"
                           value="{{ old('device_id') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm" required>
                </div>
                {{-- BOE Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">BOE Number (SAD)</label>
                    <input type="text" name="boe_number"
                           value="{{ old('boe_number') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm" required>
                </div>
                {{-- Vehicle Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Vehicle Number</label>
                    <input type="text" name="vehicle_number"
                           value="{{ old('vehicle_number') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm" required>
                </div>
                {{-- Regime --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Regime</label>
                    <select name="regime" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm" required>
                        <option value="">Select Regime</option>
                        @foreach ($regimes as $regime)
                            <option value="{{ $regime->id }}">{{ $regime->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Destination --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Destination</label>
                    <select name="destination" class="w-full mt-1 border-gray-300 rounded-lg shadow-sm" required>
                        <option value="">Select Destination</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Agency --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Agency</label>
                    <input type="text" name="agency"
                           value="{{ old('agency') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                </div>
                {{-- Agent Contact --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Agent Contact</label>
                    <input type="text" name="agent_contact"
                           value="{{ old('agent_contact') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                </div>
                {{-- Truck Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Truck Number</label>
                    <input type="text" name="truck_number"
                           value="{{ old('truck_number') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                </div>
                {{-- Driver Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Driver Name</label>
                    <input type="text" name="driver_name"
                           value="{{ old('driver_name') }}"
                           class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
                <a href="" class="bg-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm">
                    Cancel
                </a>
                <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded shadow-sm hover:bg-primary-700">
                    Create Assignment
                </button>
            </div>
        </form>
    </div>
</x-filament::page>
