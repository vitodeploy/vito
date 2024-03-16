@php
    use App\Enums\SiteFeature;
@endphp

<x-app-layout :server="$site->server">
    @if (isset($pageTitle))
        <x-slot name="pageTitle">{{ $site->domain }} - {{ $pageTitle }}</x-slot>
    @endif

    <x-slot name="header">
        <h2 class="text-lg font-semibold">
            <a href="{{ $site->getUrl() }}" target="_blank">
                {{ $site->domain }}
            </a>
        </h2>
        <div class="flex items-end">
            <div class="flex h-20 flex-col items-end justify-center">
                <div class="flex items-center">
                    <x-heroicon-o-globe-alt class="mr-1 h-5 w-5 text-gray-500" />
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
                            <x-heroicon-o-clipboard-document-check
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
                    <x-heroicon-o-server class="mr-1 h-5 w-5 text-gray-500" />
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
                            <x-heroicon-o-clipboard-document-check
                                class="h-4 w-4 font-bold text-primary-600 dark:text-white"
                            />
                        </div>
                        {{ $site->server->ip }}
                    </div>
                </x-input-label>
            </div>
        </div>
    </x-slot>

    <x-slot name="sidebar">
        <div class="flex h-16 items-center justify-center border-b border-gray-200 px-3 py-2 dark:border-gray-800">
            <div class="w-full">
                @include("layouts.partials.site-select", ["server" => $site->server, "site" => $site])
            </div>
        </div>
        <div class="space-y-2 p-3">
            <x-secondary-sidebar-link
                :href="route('servers.sites.show', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.show')"
            >
                <x-heroicon-o-globe-alt class="mr-2 h-5 w-5" />
                {{ __("Application") }}
            </x-secondary-sidebar-link>
            @if ($site->hasFeature(SiteFeature::SSL))
                <x-secondary-sidebar-link
                    :href="route('servers.sites.ssl', ['server' => $site->server, 'site' => $site])"
                    :active="request()->routeIs('servers.sites.ssl')"
                >
                    <x-heroicon-o-lock-closed class="mr-2 h-5 w-5" />
                    {{ __("SSL") }}
                </x-secondary-sidebar-link>
            @endif

            @if ($site->hasFeature(SiteFeature::QUEUES))
                <x-secondary-sidebar-link
                    :href="route('servers.sites.queues', ['server' => $site->server, 'site' => $site])"
                    :active="request()->routeIs('servers.sites.queues')"
                >
                    <x-heroicon-o-queue-list class="mr-2 h-5 w-5" />
                    {{ __("Queues") }}
                </x-secondary-sidebar-link>
            @endif

            <x-secondary-sidebar-link
                :href="route('servers.sites.settings', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.settings')"
            >
                <x-heroicon-o-cog-6-tooth class="mr-2 h-5 w-5" />
                {{ __("Settings") }}
            </x-secondary-sidebar-link>
            <x-secondary-sidebar-link
                :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.logs')"
            >
                <x-heroicon-o-square-3-stack-3d class="mr-2 h-5 w-5" />
                {{ __("Logs") }}
            </x-secondary-sidebar-link>
        </div>
    </x-slot>

    <x-container class="flex">
        <div class="w-full space-y-10">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
