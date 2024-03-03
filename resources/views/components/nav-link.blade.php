@props([
    "active",
])

@php
    $classes =
        $active ?? false
            ? "inline-flex items-center border-b-2 border-primary-400 px-1 px-2 pt-1 text-sm font-medium leading-5 text-gray-900 transition duration-150 ease-in-out focus:border-primary-700 focus:outline-none dark:border-primary-600 dark:text-gray-100"
            : "inline-flex items-center border-b-2 border-transparent px-1 px-2 pt-1 text-sm font-medium leading-5 text-gray-500 transition duration-150 ease-in-out hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-300 dark:focus:border-gray-700 dark:focus:text-gray-300";
@endphp

<a {{ $attributes->merge(["class" => $classes]) }}>
    {{ $slot }}
</a>
