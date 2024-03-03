@props([
    "active",
])

@php
    $classes =
        $active ?? false
            ? "text-md flex h-10 items-center rounded-md bg-gray-900 px-4 py-3 font-semibold text-primary-500 transition transition-all duration-100 duration-150 ease-in-out"
            : "text-md flex h-10 items-center rounded-md px-4 py-3 font-semibold text-gray-500 transition transition-all duration-100 duration-150 ease-in-out hover:bg-gray-900";
@endphp

<a {{ $attributes->merge(["class" => $classes]) }}>
    {{ $slot }}
</a>
