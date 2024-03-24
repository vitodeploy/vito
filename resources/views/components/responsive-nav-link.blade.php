@props([
    "active",
])

@php
    $classes =
        $active ?? false
            ? "block w-full border-l-4 border-primary-400 bg-primary-50 py-2 pl-3 pr-4 text-left text-base font-medium text-primary-700 transition duration-150 ease-in-out focus:border-primary-700 focus:bg-primary-100 focus:text-primary-800 focus:outline-none dark:border-primary-600 dark:bg-primary-900/50 dark:text-primary-300 dark:focus:border-primary-300 dark:focus:bg-primary-900 dark:focus:text-primary-200"
            : "block w-full border-l-4 border-transparent py-2 pl-3 pr-4 text-left text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:border-gray-600 dark:focus:bg-gray-700 dark:focus:text-gray-200";
@endphp

<a {{ $attributes->merge(["class" => $classes]) }}>
    {{ $slot }}
</a>
