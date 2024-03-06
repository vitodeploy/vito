<div>
    <x-card-header>
        <x-slot name="title">
            {{ __("Site Overview") }}
        </x-slot>
    </x-card-header>
    <div
        class="mx-auto grid grid-cols-3 rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="p-5">
            <div class="flex items-center justify-center md:justify-start">
                <div class="ml-2 hidden md:block">{{ __("SSL") }}</div>
            </div>
            <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                {{ $site->activeSsl ? __("Yes") : __("No") }}
            </div>
        </div>
        <div class="border-l border-r border-gray-200 p-5 dark:border-gray-900">
            <div class="flex items-center justify-center md:justify-start">
                <div class="ml-2 hidden md:block">{{ __("Queues") }}</div>
            </div>
            <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                {{ $site->queues()->count() }}
            </div>
        </div>
        <div class="p-5">
            <div class="flex items-center justify-center md:justify-start">
                <div class="ml-2 hidden md:block">{{ __("PHP") }}</div>
            </div>
            <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                {{ $site->php_version }}
            </div>
        </div>
    </div>
</div>
