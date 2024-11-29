<x-filament-panels::page.simple>
    <x-slot name="subheading">
        <x-filament-panels::form wire:submit="logout">
            <div>
                <x-filament-panels::form.actions :actions="$this->getLogoutActions()" :full-width="$this->hasFullWidthFormActions()" />
            </div>
        </x-filament-panels::form>
    </x-slot>

    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
