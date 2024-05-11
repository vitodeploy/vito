<div {{ $attributes->merge(["class" => "mx-auto mb-10"]) }}>
    <x-card-header>
        @if (isset($title))
            <x-slot name="title">{{ $title }}</x-slot>
        @endif

        @if (isset($description))
            <x-slot name="description">{{ $description }}</x-slot>
        @endif

        @if (isset($aside))
            <x-slot name="aside">{{ $aside }}</x-slot>
        @endif
    </x-card-header>

    <div class="mt-5">
        <div
            class="{{ isset($actions) ? "sm:rounded-tl-md sm:rounded-tr-md" : "sm:rounded-md" }} border border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:p-6"
        >
            {{ $slot }}
        </div>

        @if (isset($actions))
            <div
                class="flex items-center justify-end border border-b border-l border-r border-gray-200 border-t-transparent bg-gray-50 px-4 py-3 text-right dark:border-gray-700 dark:border-t-transparent dark:bg-gray-800 dark:bg-opacity-70 sm:rounded-bl-md sm:rounded-br-md sm:px-6"
            >
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
