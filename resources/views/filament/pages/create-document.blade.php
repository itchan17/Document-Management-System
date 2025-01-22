<x-filament-panels::page> 
        <x-filament-panels::form wire:submit="create">
            {{ $this->form }}
            <div class="left flex flex-wrap items-center gap-3">
                {{ $this->createAction }} 
                {{ $this->clearAction }}                  
            </div>
        </x-filament-panels::form>
</x-filament-panels::page>
