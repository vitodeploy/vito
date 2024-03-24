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
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-8 w-8 text-primary-500"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"
                        />
                    </svg>
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
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-8 w-8 text-primary-500"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"
                        />
                    </svg>
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
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-8 w-8 text-primary-500"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <div class="ml-2 hidden md:block">{{ __("Cron Jobs") }}</div>
            </div>
            <div class="mt-3 text-center text-3xl font-bold text-gray-600 dark:text-gray-400 md:text-left">
                {{ $server->cronJobs()->count() }}
            </div>
        </div>
    </div>
</div>
