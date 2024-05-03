@props([
    "href",
])

@php
    $class =
        "inline-flex h-9 w-max min-w-max items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-1 font-semibold text-white outline-0 transition hover:bg-primary-700 focus:border-primary-300 focus:border-primary-700 focus:ring focus:ring-primary-200 focus:ring-opacity-50 active:bg-primary-700 disabled:opacity-25 dark:focus:border-primary-700 dark:focus:ring-primary-700 dark:focus:ring-opacity-40";
@endphp

@if (isset($href))
    <button onclick="location.href = '{{ $href }}'" {{ $attributes->merge(["class" => $class]) }}>
        {{ $slot }}
    </button>
@else
    <button {{ $attributes->merge(["type" => "submit", "class" => $class]) }}>
        {{ $slot }}
    </button>
@endif
