@props([
    "href",
    "type",
    "span",
    "disabled",
])

@php
    $class =
        "inline-flex h-9 w-max min-w-max items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-1 font-semibold text-gray-700 transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-25 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800";
@endphp

@if (isset($href))
    @if (isset($disabled))
        @php
            $class .= " opacity-25 cursor-default";
        @endphp

        <span {{ $attributes->merge(["class" => $class]) }}>
            {{ $slot }}
        </span>
    @else
        <a href="{{ $href }}" {{ $attributes->merge(["class" => $class]) }}>
            {{ $slot }}
        </a>
    @endif
@else
    <button {{ $attributes->merge(["type" => $type ?? "submit", "class" => $class]) }}>
        {{ $slot }}
    </button>
@endif
