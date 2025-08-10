<x-filament::page>
    <div class="space-y-6">
        <x-filament::card>
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-medium">Device Monitorings</h2>
            </div>
        </x-filament::card>

        <div class="space-y-4">
            {{ $this->table }}
        </div>
    </div>

    @if($showAddNoteModal)
        <x-filament::modal>
            <x-slot name="title">Add Note</x-slot>

            <x-slot name="content">
                <div class="space-y-4">
                    <textarea
                        wire:model.defer="note"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Enter your note here..."
                    ></textarea>
                </div>
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-end space-x-2">
                    <x-filament::button color="secondary" wire:click="closeAddNoteModal">
                        Cancel
                    </x-filament::button>

                    <x-filament::button color="primary" wire:click="saveNote">
                        Save Note
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>
    @endif
</x-filament::page>