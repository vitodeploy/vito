<div class="mx-auto mb-10">
    <x-card-header>
        @if(isset($title))
            <x-slot name="title">{{ $title }}</x-slot>
        @endif
        @if(isset($description))
            <x-slot name="description">{{ $description }}</x-slot>
        @endif
        @if(isset($aside))
            <x-slot name="aside">{{ $aside }}</x-slot>
        @endif
    </x-card-header>

    <div class="mt-5">
        <div class="bg-white px-4 py-5 dark:bg-gray-800 sm:p-6 border border-gray-200 dark:border-gray-700 {{ isset($actions) ? 'sm:rounded-tl-md sm:rounded-tr-md': 'sm:rounded-md' }}">
            {{ $slot }}
        </div>

        @if(isset($actions))
            <div class="flex items-center justify-end bg-gray-50 border border-r border-b border-l border-t-transparent dark:border-t-transparent border-gray-200 dark:border-gray-700 px-4 py-3 text-right dark:bg-gray-800 dark:bg-opacity-70 sm:rounded-bl-md sm:rounded-br-md sm:px-6">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
