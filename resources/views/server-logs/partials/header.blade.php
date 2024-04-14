@if (isset($pageTitle))
    <x-slot name="pageTitle">{{ $pageTitle }}</x-slot>
@endif

<x-slot name="header">
    <div class="hidden md:flex md:items-center md:justify-start">
        <x-tab-item
            class="mr-1"
            :href="route('servers.logs', ['server' => $server])"
            :active="request()->routeIs('servers.logs')"
        >
            <x-heroicon name="o-square-3-stack-3d" class="h-5 w-5" />
            <span class="ml-2 hidden xl:block">{{ __("Vito Logs") }}</span>
        </x-tab-item>
        <x-tab-item
            class="mr-1"
            :href="route('servers.logs.remote', ['server' => $server])"
            :active="request()->routeIs('servers.logs.remote')"
        >
            <x-heroicon name="o-document-magnifying-glass" class="h-5 w-5" />
            <span class="ml-2 hidden xl:block">{{ __("Remote Logs") }}</span>
        </x-tab-item>
    </div>
    <div class="md:hidden">
        <x-dropdown align="left">
            <x-slot name="trigger">
                <div
                    class="flex w-full cursor-pointer items-center rounded-md border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                    Select
                    <button type="button" class="ml-2">
                        <x-heroicon name="o-chevron-down" class="h-4 w-4 text-gray-400" />
                    </button>
                </div>
            </x-slot>
            <x-slot name="content">
                <x-dropdown-link
                    :href="route('servers.logs', ['server' => $server])"
                    :active="request()->routeIs('servers.logs')"
                >
                    <x-heroicon name="o-cog-6-tooth" class="h-5 w-5" />
                    <span class="ml-2">{{ __("Vito Logs") }}</span>
                </x-dropdown-link>
                <x-dropdown-link
                    :href="route('servers.logs.remote', ['server' => $server])"
                    :active="request()->routeIs('servers.logs.remote')"
                >
                    <x-heroicon name="o-document-magnifying-glass" class="h-5 w-5" />
                    <span class="ml-2">{{ __("Remote Logs") }}</span>
                </x-dropdown-link>
            </x-slot>
        </x-dropdown>
    </div>
</x-slot>
