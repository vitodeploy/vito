@props([
    "name",
    "title",
    "description",
    "method",
    "action" => "",
])

<x-modal :name="$name">
    <form
        id="{{ $name }}-form"
        method="post"
        action="{{ $action }}"
        {{ $attributes }}
        class="p-6"
    >
        @csrf
        @method($method)
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Confirm") }}
        </h2>
        <p>{{ $description }}</p>
        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>
            <x-danger-button class="ml-3">
                {{ __("Confirm") }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
