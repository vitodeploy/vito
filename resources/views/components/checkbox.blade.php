@props([
    "disabled" => false,
    "id",
    "name",
    "value",
])

<div class="flex items-center">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="checkbox"
        value="{{ $value }}"
        {{ $attributes->merge(["disabled" => $disabled, "class" => "rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"]) }}
    />
    <label for="{{ $id }}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
        {{ $slot }}
    </label>
</div>
