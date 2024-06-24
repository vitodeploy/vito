<div
    {{ $attributes->merge(["class" => "font-mono whitespace-pre relative h-[500px] w-full overflow-auto whitespace-pre-line rounded-md border border-gray-200 bg-black p-5 text-gray-50 dark:border-gray-700"]) }}
>
    {{ $slot }}
</div>
