<div
    {!! $attributes->merge(["class" => "inline-block min-w-full max-w-full overflow-x-auto rounded-md bg-white align-middle border border-gray-200 dark:border-gray-700 dark:bg-gray-800"]) !!}
>
    <table class="min-w-full">
        {{ $slot }}
    </table>
</div>
