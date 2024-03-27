<div
    {{ $attributes->merge(["class" => "flex h-20 items-center justify-between rounded-b-md rounded-t-md border border-gray-200 bg-white p-7 text-center dark:border-gray-700 dark:bg-gray-800"]) }}
>
    {{ $slot }}
</div>
