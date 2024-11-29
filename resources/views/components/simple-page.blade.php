<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <x-slot name="subheading">
        <x-filament-panels::form
            wire:submit="logout"
            style="display: flex !important; justify-content: flex-end !important; width: 100% !important"
        >
            <div>
                <x-filament-panels::form.actions
                    :actions="$this->getLogoutActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </div>
        </x-filament-panels::form>
    </x-slot>
</x-filament-panels::page.simple>