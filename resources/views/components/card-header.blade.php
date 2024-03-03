<div class="@if(isset($aside)) flex justify-between @endif mb-6">
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

    <div>
        @if (isset($aside))
            {{ $aside }}
        @endif
    </div>
</div>
