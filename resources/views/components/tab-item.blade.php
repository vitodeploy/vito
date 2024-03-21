@props([
    "active",
])

@php
    $classes =
        $active ?? false
            ? "flex h-10 items-center justify-start rounded-lg bg-primary-50 px-3 py-2 font-semibold text-primary-500 dark:bg-primary-500 dark:bg-opacity-20"
            : "flex h-10 items-center justify-start rounded-lg px-3 py-2 font-semibold text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700";
@endphp

<a {{ $attributes->merge(["class" => $classes]) }}>
    {{ $slot }}
</a>
