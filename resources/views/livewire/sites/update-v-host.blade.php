<x-card>
    <x-slot name="title">{{ __('Update VHost') }}</x-slot>

    <x-slot name="description">{{ __("You can change your site's VHost configuration") }}</x-slot>

    <form
        id="update-vhost"
        wire:submit="update"
        class="space-y-6"
    >
        <div>
            <x-textarea
                id="vHost"
                wire:init="loadVHost"
                wire:model="vHost"
                rows="10"
                class="mt-1 block w-full"
            ></x-textarea>
            @error('vHost')
            <x-input-error
                class="mt-2"
                :messages="$message"
            />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button
            form="update-vhost"
            wire:loading.attr="disabled"
        >{{ __('Save') }}</x-primary-button>
    </x-slot>
</x-card>
