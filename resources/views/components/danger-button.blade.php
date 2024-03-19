<button
    {{ $attributes->merge(["type" => "submit", "class" => "inline-flex w-max min-w-max items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-1 h-9 font-semibold capitalize text-white transition hover:bg-red-500 focus:border-red-700 focus:outline-none focus:ring focus:ring-red-200 active:bg-red-600 disabled:opacity-25"]) }}
>
    {{ $slot }}
</button>
