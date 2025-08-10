<x-filament::page>
    <div class="space-y-6">
        <!-- Page Header -->
        <x-filament::card>
            <h2 class="text-lg font-medium">Confirmed Affixed Devices</h2>
        </x-filament::card>

        <!-- Actions Section -->
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <!-- Picked for Affixing Button -->
               
            </div>

            <!-- Table Component -->
            <div class="mt-4 overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    {{ $this->table }}
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($showConfirmModal ?? false)
        <x-filament::modal
            visible="true"
            wire:key="confirm-pick-modal"
        >
            <x-slot name="title">
                Confirm for Affixing
            </x-slot>

            <x-slot name="content">
                <p>Are you sure you want to confirm the selected devices for affixing?</p>
            </x-slot>

            <x-slot name="footer">
                <x-filament::button
                    color="secondary"
                    wire:click="$set('showConfirmModal', false)"
                >
                    No
                </x-filament::button>

                <x-filament::button
                    color="success"
                    wire:click="confirmPickedForAffixing"
                >
                    Yes
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endif
</x-filament::page>
