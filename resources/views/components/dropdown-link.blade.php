@props(["active" => false])

@php
    $class = $active
        ? "block flex w-full items-center justify-start px-4 py-2 text-left text-sm leading-5 text-primary-500 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:hover:bg-gray-800/50 dark:focus:bg-gray-700"
        : "block flex w-full items-center justify-start px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800/50 dark:focus:bg-gray-700";
@endphp

<a {{ $attributes->merge(["class" => $class]) }}>
    {{ $slot }}
</a>
