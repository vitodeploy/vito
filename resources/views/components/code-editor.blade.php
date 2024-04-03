@props([
    "id",
    "name",
    "disabled" => false,
    "lang" => "text",
])

<div>
    <textarea
        id="text-{{ $id }}"
        name="{{ $name }}"
        class="min-h-[400px] w-full rounded-md border border-gray-200 dark:border-gray-700"
    >
{{ $slot }}</textarea
    >
</div>
