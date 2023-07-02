@props(['active'])

@php
$classes = ($active ?? false)
            ? 'text-md font-semibold flex items-center text-primary-500 transition duration-150 ease-in-out mb-4'
            : 'text-md font-semibold flex items-center hover:text-primary-600 text-gray-600 dark:text-gray-500 transition duration-150 ease-in-out mb-4';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
