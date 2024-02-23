<x-modal name="database-user-password">
    <form wire:submit="create" class="p-6" x-data="{remote: false}">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('View Password') }}
        </h2>

        <div class="mt-6">
            <x-input-label :value="__('Password')" />
            <x-text-input wire:model="viewPassword" type="text" class="mt-1 w-full" disabled />
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Close') }}
            </x-secondary-button>

            <x-primary-button x-data="{ copied: false }" x-clipboard.raw="{{ $viewPassword }}" class="ml-2">
                <div x-show="copied" class="flex items-center">{{ __("Copied") }}</div>
                <div x-show="!copied" x-on:click="copied = true; setTimeout(() => {copied = false}, 2000)">{{ __("Copy") }}</div>
            </x-primary-button>
        </div>
    </form>
</x-modal>
