<div>
    <x-filament::modal wire:model="showDispatchModal">
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-900">
                Dispatch Device
            </h2>
        </x-slot>

        <div class="p-6 space-y-4">
            {{-- Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Dispatch Date</label>
                <x-filament::input 
                    type="text" 
                    wire:model="dispatchDate" 
                    disabled 
                    class="mt-1 block w-full rounded-md"
                />
            </div>

            {{-- SAD/T1 Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">SAD/T1 Number</label>
                <x-filament::input 
                    type="text" 
                    wire:model.defer="sadNumber" 
                    required 
                    class="mt-1 block w-full rounded-md"
                />
                @error('sadNumber') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Vehicle Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Vehicle Number</label>
                <x-filament::input 
                    type="text" 
                    wire:model.defer="vehicleNumber" 
                    required 
                    class="mt-1 block w-full rounded-md"
                />
                @error('vehicleNumber') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Regime --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Regime</label>
                <select 
                    wire:model.defer="regime" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                >
                    <option value="">Select Regime</option>
                    <option value="TRANSIT">Transit</option>
                    <option value="IM4">IM4</option>
                    <option value="IM7">IM7</option>
                    <option value="IM8">IM8</option>
                </select>
                @error('regime') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Route --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Route</label>
                <select 
                    wire:model.defer="route_id" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                >
                    <option value="">Select Route</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                    @endforeach
                </select>
                @error('route_id') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>

            {{-- Manifest Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Manifest Date</label>
                <x-filament::input 
                    type="date" 
                    wire:model.defer="manifestDate" 
                    required 
                    class="mt-1 block w-full rounded-md"
                />
                @error('manifestDate') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end space-x-2">
                <x-filament::button 
                    wire:click="closeDispatchModal" 
                    color="secondary"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button 
                    wire:click="dispatchDevices" 
                    class="!bg-orange-500 hover:!bg-orange-600"
                >
                    Dispatch
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
