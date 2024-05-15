@props([
    "status",
])

@php
    $class = [
        "success" => "max-w-max rounded-md border border-green-300 bg-green-50 px-2 py-1 text-xs uppercase text-green-500 dark:border-green-600 dark:bg-green-500 dark:bg-opacity-10",
        "danger" => "max-w-max rounded-md border border-red-300 bg-red-50 px-2 py-1 text-xs uppercase text-red-500 dark:border-red-600 dark:bg-red-500 dark:bg-opacity-10",
        "warning" => "max-w-max rounded-md border border-yellow-300 bg-yellow-50 px-2 py-1 text-xs uppercase text-yellow-500 dark:border-yellow-600 dark:bg-yellow-500 dark:bg-opacity-10",
        "disabled" => "max-w-max rounded-md border border-gray-300 bg-gray-50 px-2 py-1 text-xs uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-500 dark:bg-opacity-30 dark:text-gray-400",
        "info" => "max-w-max rounded-md border border-primary-300 bg-primary-50 px-2 py-1 text-xs uppercase text-primary-500 dark:border-primary-600 dark:bg-primary-500 dark:bg-opacity-10",
    ];
@endphp

<div {{ $attributes->merge(["class" => $class[$status]]) }}>
    {{ $slot }}
</div>
