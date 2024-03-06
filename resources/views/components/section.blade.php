<div
    {!! $attributes->merge(["class" => "flex justify-between md:col-span-1 mb-5"]) !!}
>
    @if (isset($title) || isset($description))
        <div>
            @if (isset($title))
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-300">
                    {{ $title }}
                </h3>
            @endif

            @if (isset($description))
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    @if (isset($aside))
        <div>
            {{ $aside }}
        </div>
    @endif
</div>
