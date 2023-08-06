@props(['active'])

@php
$classes = ($active ?? false)
            ? 'h-10 rounded-md px-4 py-3 text-md font-semibold flex items-center bg-gray-900 text-primary-500 transition duration-150 ease-in-out transition-all duration-100'
            : 'h-10 rounded-md px-4 py-3 text-md font-semibold flex items-center text-gray-500 transition duration-150 ease-in-out transition-all duration-100 hover:bg-gray-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
