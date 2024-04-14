<div>
    <x-card-header>
        <x-slot name="title">
            {{ __("Server Overview") }}
        </x-slot>
        <x-slot name="description">
            {{ __("You can see an overview about your server here") }}
        </x-slot>
    </x-card-header>
    <div
        class="@if($server->webserver() && $server->database()) grid-cols-3 @else grid-cols-2 @endif mx-auto grid rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
        @if ($server->webserver())
            <div class="border-r border-gray-200 p-5 dark:border-gray-900">
                <div class="flex items-center justify-center md:justify-start">
                    <x-heroicon name="o-globe-alt" class="h-8 w-8 text-primary-500" />
                    <div class="ml-2 hidden md:block">{{ __("Sites") }}</div>
                </div>
                <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                    {{ $server->sites()->count() }}
                </div>
            </div>
        @endif

        @if ($server->database())
            <div class="border-r border-gray-200 p-5 dark:border-gray-900">
                <div class="flex items-center justify-center md:justify-start">
                    <x-heroicon name="o-circle-stack" class="h-8 w-8 text-primary-500" />
                    <div class="ml-2 hidden md:block">
                        {{ __("Databases") }}
                    </div>
                </div>
                <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                    {{ $server->databases()->count() }}
                </div>
            </div>
        @endif

        <div class="p-5">
            <div class="flex items-center justify-center md:justify-start">
                <x-heroicon name="o-clock" class="h-8 w-8 text-primary-500" />
                <div class="ml-2 hidden md:block">{{ __("Cron Jobs") }}</div>
            </div>
            <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                {{ $server->cronJobs()->count() }}
            </div>
        </div>
    </div>
</div>
