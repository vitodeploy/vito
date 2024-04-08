<x-server-layout :server="$server">
    @include("server-logs.partials.header")


    <div>
        <x-card-header>
            <x-slot name="title">{{ __("Remote Logs") }}</x-slot>
            <x-slot name="description">
                {{ __("Here you can add new logs") }}
            </x-slot>
            <x-slot name="aside">
                <div class="flex flex-col items-end lg:flex-row lg:items-center">
                    <x-dropdown>
                        <x-slot name="trigger">
                            <x-secondary-button>
                                {{ __("Manage") }}
                                <x-heroicon name="o-chevron-down" class="ml-1 h-4 w-4" />
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link
                                class="cursor-pointer"
                                x-on:click="$dispatch('open-modal', 'add-log')"
                            >
                                {{ __("Add Log") }}
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                    @include("server-logs.partials.add-log")
                </div>
            </x-slot>
        </x-card-header>
    </div>


    @include("server-logs.partials.logs-list")
</x-server-layout>
