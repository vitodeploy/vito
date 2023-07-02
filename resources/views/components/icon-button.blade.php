@props(['href'])

@php
    $class = 'w-max inline-flex items-center justify-center px-2 py-1 font-semibold capitalize transition hover:opacity-50 outline-0 focus:ring focus:ring-primary-200 disabled:opacity-25 dark:focus:ring-primary-700 dark:focus:ring-opacity-40';
@endphp

@if(isset($href))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>
        {{ $slot }}
    </button>
@endif
