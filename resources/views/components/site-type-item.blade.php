@props([
    "active",
])

@php
    $class =
        "flex w-full cursor-pointer items-center justify-center rounded-md border-2 bg-primary-50 px-3 pb-2 pt-3 dark:bg-primary-500 dark:bg-opacity-10";
    $classes =
        $active ?? false
            ? $class . " border-primary-600"
            : $class . " border-primary-200 dark:border-primary-600 dark:border-opacity-20";
@endphp

<div {{ $attributes->merge(["class" => $classes]) }}>
    {{ $slot }}
</div>
