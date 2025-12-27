<x-filament::modal>
    <x-slot name="header">
        Edit Assignment
    </x-slot>

    {{ $this->form }}

    <x-slot name="footer">
        <x-filament::button
            type="submit"
            wire:click="save"
        >
            Save Changes
        </x-filament::button>
    </x-slot>
</x-filament::modal>
