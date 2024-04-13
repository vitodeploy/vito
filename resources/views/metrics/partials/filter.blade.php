<div class="flex items-center" x-data="{ period: '{{ request()->query("period") ?? "10m" }}' }">
    <x-dropdown align="left" class="ml-2">
        <x-slot name="trigger">
            <div data-tooltip="Change Period">
                <div
                    class="flex w-full items-center rounded-md border border-gray-300 bg-white p-2.5 pr-10 text-sm capitalize text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                    <div x-text="period"></div>
                </div>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-2">
                    <x-heroicon name="o-chevron-down" class="h-4 w-4 text-gray-400" />
                </button>
            </div>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('servers.metrics', ['server' => $server, 'period' => '10m'])">
                10 Minutes
            </x-dropdown-link>
            <x-dropdown-link :href="route('servers.metrics', ['server' => $server, 'period' => '30m'])">
                30 Minutes
            </x-dropdown-link>
            <x-dropdown-link :href="route('servers.metrics', ['server' => $server, 'period' => '1h'])">
                1 Hour
            </x-dropdown-link>
            <x-dropdown-link :href="route('servers.metrics', ['server' => $server, 'period' => '12h'])">
                12 Hours
            </x-dropdown-link>
            <x-dropdown-link :href="route('servers.metrics', ['server' => $server, 'period' => '1d'])">
                1 Day
            </x-dropdown-link>
            <x-dropdown-link :href="route('servers.metrics', ['server' => $server, 'period' => '7d'])">
                7 Days
            </x-dropdown-link>
            <x-dropdown-link x-on:click="period = 'custom'" class="cursor-pointer">Custom</x-dropdown-link>
        </x-slot>
    </x-dropdown>

    <form
        x-show="period === 'custom'"
        class="flex items-center"
        action="{{ route("servers.metrics", ["server" => $server, "period" => "custom"]) }}"
    >
        <input type="hidden" name="period" value="custom" />
        <div date-rangepicker datepicker-format="yyyy-mm-dd" class="ml-2 flex items-center">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <x-heroicon name="o-calendar" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                </div>
                <input
                    name="from"
                    type="text"
                    class="block w-full rounded-md border border-gray-300 bg-white p-2.5 ps-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                    placeholder="{{ __("From Date") }}"
                    value="{{ request()->query("from") }}"
                    autocomplete="off"
                />
            </div>
            <span class="mx-2 text-gray-500">to</span>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <x-heroicon name="o-calendar" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                </div>
                <input
                    name="to"
                    type="text"
                    class="block w-full rounded-md border border-gray-300 bg-white p-2.5 ps-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                    placeholder="{{ __("To Date") }}"
                    value="{{ request()->query("to") }}"
                    autocomplete="off"
                />
                <x-input-error class="absolute left-0 top-10 ml-1 mt-1" :messages="$errors->get('to')" />
            </div>
        </div>
        <x-primary-button class="ml-2 h-[42px]">{{ __("Filter") }}</x-primary-button>
    </form>
</div>
