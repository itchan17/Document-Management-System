<x-filament-panels::page>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}
        <div class="left flex flex-wrap items-center gap-3">
            <x-filament::button form="create" type="submit">
                Upload Document
            </x-filament::button>
            <x-filament::button color="gray" wire:click="clear" type="button">
                Clear
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
