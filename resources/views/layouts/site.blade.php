@php
    use App\Enums\SiteFeature;
@endphp

<x-app-layout :server="$site->server">
    @if (isset($pageTitle))
        <x-slot name="pageTitle">{{ $site->domain }} - {{ $pageTitle }}</x-slot>
    @endif

    <x-slot name="header">
        <div class="hidden md:flex md:items-center md:justify-start">
            <x-tab-item
                class="mr-1"
                :href="route('servers.sites.show', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.show')"
            >
                <x-heroicon name="o-globe-alt" class="h-5 w-5" />
                <span class="ml-2 hidden xl:block">Application</span>
            </x-tab-item>
            @if ($site->hasFeature(SiteFeature::SSL))
                <x-tab-item
                    class="mr-1"
                    :href="route('servers.sites.ssl', ['server' => $site->server, 'site' => $site])"
                    :active="request()->routeIs('servers.sites.ssl')"
                >
                    <x-heroicon name="o-lock-closed" class="h-5 w-5" />
                    <span class="ml-2 hidden xl:block">SSL</span>
                </x-tab-item>
            @endif

            @if ($site->hasFeature(SiteFeature::QUEUES) && $site->server->processManager()?->status == \App\Enums\ServiceStatus::READY)
                <x-tab-item
                    class="mr-1"
                    :href="route('servers.sites.queues', ['server' => $site->server, 'site' => $site])"
                    :active="request()->routeIs('servers.sites.queues')"
                >
                    <x-heroicon name="o-queue-list" class="h-5 w-5" />
                    <span class="ml-2 hidden xl:block">Queues</span>
                </x-tab-item>
            @endif

            <x-tab-item
                class="mr-1"
                :href="route('servers.sites.settings', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.settings')"
            >
                <x-heroicon name="o-cog-6-tooth" class="h-5 w-5" />
                <span class="ml-2 hidden xl:block">Settings</span>
            </x-tab-item>
            <x-tab-item
                class="mr-1"
                :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.logs*')"
            >
                <x-heroicon name="o-square-3-stack-3d" class="h-5 w-5" />
                <span class="ml-2 hidden xl:block">Logs</span>
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
                        :href="route('servers.sites.show', ['server' => $site->server, 'site' => $site])"
                        :active="request()->routeIs('servers.sites.show')"
                    >
                        <x-heroicon name="o-globe-alt" class="h-5 w-5" />
                        <span class="ml-2">Application</span>
                    </x-dropdown-link>
                    @if ($site->hasFeature(SiteFeature::SSL))
                        <x-dropdown-link
                            :href="route('servers.sites.ssl', ['server' => $site->server, 'site' => $site])"
                            :active="request()->routeIs('servers.sites.ssl')"
                        >
                            <x-heroicon name="o-lock-closed" class="h-5 w-5" />
                            <span class="ml-2">SSL</span>
                        </x-dropdown-link>
                    @endif

                    @if ($site->hasFeature(SiteFeature::QUEUES))
                        <x-dropdown-link
                            :href="route('servers.sites.queues', ['server' => $site->server, 'site' => $site])"
                            :active="request()->routeIs('servers.sites.queues')"
                        >
                            <x-heroicon name="o-queue-list" class="h-5 w-5" />
                            <span class="ml-2">Queues</span>
                        </x-dropdown-link>
                    @endif

                    <x-dropdown-link
                        :href="route('servers.sites.settings', ['server' => $site->server, 'site' => $site])"
                        :active="request()->routeIs('servers.sites.settings')"
                    >
                        <x-heroicon name="o-cog-6-tooth" class="h-5 w-5" />
                        <span class="ml-2">Settings</span>
                    </x-dropdown-link>
                    <x-dropdown-link
                        :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])"
                        :active="request()->routeIs('servers.sites.logs*')"
                    >
                        <x-heroicon name="o-square-3-stack-3d" class="h-5 w-5" />
                        <span class="ml-2">Logs</span>
                    </x-dropdown-link>
                </x-slot>
            </x-dropdown>
        </div>
        <div class="flex items-end">
            <div class="flex h-20 flex-col items-end justify-center">
                <div class="flex items-center">
                    <x-heroicon name="o-globe-alt" class="mr-1 h-5 w-5 text-gray-500" />
                    @include("sites.partials.site-status")
                </div>
                <x-input-label class="mt-1 cursor-pointer" x-data="{ copied: false }">
                    <div
                        class="flex items-center text-sm"
                        x-on:click="
                            window.copyToClipboard('{{ $site->domain }}')
                            copied = true
                            setTimeout(() => {
                                copied = false
                            }, 2000)
                        "
                    >
                        <div x-show="copied" class="mr-1 flex items-center">
                            <x-heroicon
                                name="o-clipboard-document-check"
                                class="h-4 w-4 font-bold text-primary-600 dark:text-white"
                            />
                        </div>
                        {{ $site->domain }}
                    </div>
                </x-input-label>
            </div>
            <div class="mx-5 h-20 border-r border-gray-200 dark:border-gray-800"></div>
            <div class="flex h-20 flex-col items-end justify-center">
                <div class="flex items-center">
                    <x-heroicon name="o-server" class="mr-1 h-5 w-5 text-gray-500" />
                    @include("servers.partials.server-status", ["server" => $site->server])
                </div>
                <x-input-label class="mt-1 cursor-pointer" x-data="{ copied: false }">
                    <div
                        class="flex items-center text-sm"
                        x-on:click="
                            window.copyToClipboard('{{ $site->server->ip }}')
                            copied = true
                            setTimeout(() => {
                                copied = false
                            }, 2000)
                        "
                    >
                        <div x-show="copied" class="mr-1 flex items-center">
                            <x-heroicon
                                name="o-clipboard-document-check"
                                class="h-4 w-4 font-bold text-primary-600 dark:text-white"
                            />
                        </div>
                        {{ $site->server->ip }}
                    </div>
                </x-input-label>
            </div>
        </div>
    </x-slot>

    <x-container class="flex">
        <div class="w-full space-y-10">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
