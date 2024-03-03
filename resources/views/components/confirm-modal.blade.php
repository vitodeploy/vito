@props([
    "name",
    "input",
    "title",
    "description",
    "method",
])

<x-modal :name="$name">
    <form wire:submit="{{ $method }}" class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Confirm") }}
        </h2>
        <p>{{ $description }}</p>
        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>
            <x-danger-button
                class="ml-3"
                @confirmed.window="$dispatch('close')"
                wire:loading.attr="disabled"
            >
                {{ __("Confirm") }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
