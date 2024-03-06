@php
    use App\Enums\SiteFeature;
@endphp

<x-app-layout :server="$site->server">
    @if (isset($pageTitle))
        <x-slot name="pageTitle">{{ $site->domain }} - {{ $pageTitle }}</x-slot>
    @endif

    <x-slot name="header">
        <h2 class="text-lg font-semibold">
            <a href="{{ $site->activeSsl ? "https://" : "http://" . $site->domain }}" target="_blank">
                {{ $site->domain }}
            </a>
        </h2>
        <div class="flex items-end">
            <div class="flex h-20 flex-col items-end justify-center">
                <div class="flex items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="mr-1 h-5 w-5 text-gray-500"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"
                        />
                    </svg>
                    @include("sites.partials.site-status")
                </div>
                <x-input-label
                    class="mt-1 cursor-pointer"
                    x-data="{ copied: false }"
                    x-clipboard.raw="{{ $site->web_directory_path }}"
                >
                    <div
                        class="flex items-center text-sm"
                        x-on:click="
                            copied = true
                            setTimeout(() => {
                                copied = false
                            }, 2000)
                        "
                    >
                        <div x-show="copied" class="mr-1 flex items-center">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-4 w-4 font-bold text-primary-600 dark:text-white"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"
                                />
                            </svg>
                        </div>
                        {{ $site->domain }}
                    </div>
                </x-input-label>
            </div>
            <div class="mx-5 h-20 border-r border-gray-200 dark:border-gray-800"></div>
            <div class="flex h-20 flex-col items-end justify-center">
                <div class="flex items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="mr-1 h-5 w-5 text-gray-500"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008zm-3 0h.008v.008h-.008v-.008z"
                        />
                    </svg>
                    @include("servers.partials.server-status", ["server" => $site->server])
                </div>
                <x-input-label
                    class="mt-1 cursor-pointer"
                    x-data="{ copied: false }"
                    x-clipboard.raw="{{ $site->server->ip }}"
                >
                    <div
                        class="flex items-center text-sm"
                        x-on:click="
                            copied = true
                            setTimeout(() => {
                                copied = false
                            }, 2000)
                        "
                    >
                        <div x-show="copied" class="mr-1 flex items-center">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-4 w-4 font-bold text-primary-600 dark:text-white"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"
                                />
                            </svg>
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
                {{ __("Application") }}
            </x-secondary-sidebar-link>
            @if ($site->isReady() && $site->hasFeature(SiteFeature::SSL))
                <x-secondary-sidebar-link
                    :href="route('servers.sites.ssl', ['server' => $site->server, 'site' => $site])"
                    :active="request()->routeIs('servers.sites.ssl')"
                >
                    {{ __("SSL") }}
                </x-secondary-sidebar-link>
            @endif

            @if ($site->isReady() && $site->hasFeature(SiteFeature::QUEUES))
                <x-secondary-sidebar-link
                    :href="route('servers.sites.queues', ['server' => $site->server, 'site' => $site])"
                    :active="request()->routeIs('servers.sites.queues')"
                >
                    {{ __("Queues") }}
                </x-secondary-sidebar-link>
            @endif

            <x-secondary-sidebar-link
                :href="route('servers.sites.settings', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.settings')"
            >
                {{ __("Settings") }}
            </x-secondary-sidebar-link>
            <x-secondary-sidebar-link
                :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])"
                :active="request()->routeIs('servers.sites.logs')"
            >
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
