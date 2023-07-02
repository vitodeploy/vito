@props(['href', 'type', 'span', 'disabled'])

@php
    $class = 'inline-flex items-center px-4 py-1 h-9 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150';
@endphp

@if(isset($href))
    @if(isset($disabled))
        @php
            $class .= ' opacity-25 cursor-default';
        @endphp
        <span {{ $attributes->merge(['class' => $class]) }}>
            {{ $slot }}
        </span>
    @else
        <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
            {{ $slot }}
        </a>
    @endif
@else
    <button {{ $attributes->merge(['type' => $type ?? 'submit', 'class' => $class]) }}>
        {{ $slot }}
    </button>
@endif
