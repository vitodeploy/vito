@props([
    "href",
    "type",
    "span",
    "disabled",
    "active" => false,
    "icon" => null,
    "iconAlign" => "left",
])

@php
    $class = "inline-flex h-9 w-max min-w-max items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-1 font-semibold text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-25 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800";

    if ($active === true) {
        $class = "inline-flex h-9 w-max min-w-max items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-1 font-semibold text-white outline-0 transition hover:bg-primary-700 focus:border-primary-300 focus:border-primary-700 focus:ring focus:ring-primary-200 focus:ring-opacity-50 active:bg-primary-700 disabled:opacity-25 dark:focus:border-primary-700 dark:focus:ring-primary-700 dark:focus:ring-opacity-40";
    }

    if ($iconAlign == "right") {
        $class .= " flex-row-reverse ";
        $iconClass = " ml-1 ";
    } else {
        $iconClass = " mr-1 ";
    }
@endphp

@if (isset($href))
    @if (isset($disabled))
        @php
            $class .= " opacity-25 cursor-default";
        @endphp

        <span {{ $attributes->merge(["class" => $class]) }}>
            @if ($icon !== null)
                <x-heroicon :name="$icon" class="h-5 w-5 {{ $iconClass }}" />
            @endif

            {{ $slot }}
        </span>
    @else
        <a href="{{ $href }}" {{ $attributes->merge(["class" => $class]) }}>
            @if ($icon !== null)
                <x-heroicon :name="$icon" class="h-5 w-5 {{ $iconClass }}" />
            @endif

            {{ $slot }}
        </a>
    @endif
@else
    <button {{ $attributes->merge(["type" => $type ?? "submit", "class" => $class]) }}>
        @if ($icon !== null)
            <x-heroicon :name="$icon" class="h-5 w-5 {{ $iconClass }}" />
        @endif

        {{ $slot }}
    </button>
@endif
