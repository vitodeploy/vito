@props(['active'])

@php
$classes = ($active ?? false)
            ? 'h-10 flex items-center justify-start rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 bg-primary-50 dark:bg-primary-500 dark:bg-opacity-20 text-primary-500 font-semibold'
            : 'h-10 flex items-center justify-start rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-300 font-semibold';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
