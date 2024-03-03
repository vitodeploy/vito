@props([
    "status",
])

@php
    $class = [
        "success" => "rounded-full bg-green-50 px-2 py-1 text-xs uppercase text-green-500 dark:bg-green-500 dark:bg-opacity-10",
        "danger" => "rounded-full bg-red-50 px-2 py-1 text-xs uppercase text-red-500 dark:bg-red-500 dark:bg-opacity-10",
        "warning" => "rounded-full bg-yellow-50 px-2 py-1 text-xs uppercase text-yellow-500 dark:bg-yellow-500 dark:bg-opacity-10",
        "disabled" => "rounded-full bg-gray-50 px-2 py-1 text-xs uppercase text-gray-500 dark:bg-gray-500 dark:bg-opacity-30 dark:text-gray-400",
        "info" => "rounded-full bg-primary-50 px-2 py-1 text-xs uppercase text-primary-500 dark:bg-primary-500 dark:bg-opacity-10",
    ];
@endphp

<div {{ $attributes->merge(["class" => $class[$status]]) }}>
    {{ $slot }}
</div>
