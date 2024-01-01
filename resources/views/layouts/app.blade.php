@props(['server'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @if (isset($pageTitle))
            {{ $pageTitle }} -
        @endif
        {{ config('app.name', 'Laravel') }}
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <script src="{{ asset('static/libs/ace/ace.js') }}"></script>
    <script src="{{ asset('static/libs/ace/theme-github.js') }}"></script>
    <script src="{{ asset('static/libs/ace/theme-one-dark.js') }}"></script>
    <script src="{{ asset('static/libs/ace/mode-sh.js') }}"></script>

    @include('layouts.partials.favicon')
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 dark:text-gray-300 min-h-screen min-w-max"
    x-data="" x-cloak>
    <div class="flex min-h-screen">
        <div
            class="left-0 top-0 min-h-screen w-64 flex-none bg-gray-800 dark:bg-gray-800/50 p-3 dark:border-r-2 dark:border-gray-800">
            <div class="h-16 block">
                <div class="flex items-center justify-start text-3xl font-extrabold text-white">
                    <x-application-logo class="w-10 h-10 rounded-md"/>
                    <span class="ml-1">Deploy</span>
                </div>
            </div>

            <div class="mb-5 space-y-2">
                @include('layouts.partials.server-select', ['server' => isset($server) ? $server : null])

                @if (isset($server))
                    <x-sidebar-link :href="route('servers.show', ['server' => $server])" :active="request()->routeIs('servers.show')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <span class="ml-2 text-gray-50">{{ __('Overview') }}</span>
                    </x-sidebar-link>
                    @if ($server->isReady())
                        @if ($server->webserver())
                            <x-sidebar-link :href="route('servers.sites', ['server' => $server])" :active="request()->routeIs('servers.sites') || request()->is('servers/*/sites/*')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                                <span class="ml-2 text-gray-50">{{ __('Sites') }}</span>
                            </x-sidebar-link>
                        @endif
                        @if ($server->database())
                            <x-sidebar-link :href="route('servers.databases', ['server' => $server])" :active="request()->routeIs('servers.databases') ||
                                request()->routeIs('servers.databases.backups')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                </svg>
                                <span class="ml-2 text-gray-50">{{ __('Databases') }}</span>
                            </x-sidebar-link>
                        @endif
                        @if ($server->php())
                            <x-sidebar-link :href="route('servers.php', ['server' => $server])" :active="request()->routeIs('servers.php')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                                </svg>
                                <span class="ml-2 text-gray-50">{{ __('PHP') }}</span>
                            </x-sidebar-link>
                        @endif
                        @if ($server->firewall())
                            <x-sidebar-link :href="route('servers.firewall', ['server' => $server])" :active="request()->routeIs('servers.firewall')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                                </svg>
                                <span class="ml-2 text-gray-50">{{ __('Firewall') }}</span>
                            </x-sidebar-link>
                        @endif
                        <x-sidebar-link :href="route('servers.cronjobs', ['server' => $server])" :active="request()->routeIs('servers.cronjobs')">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="ml-2 text-gray-50">{{ __('Cronjobs') }}</span>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('servers.ssh-keys', ['server' => $server])" :active="request()->routeIs('servers.ssh-keys')">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                            </svg>
                            <span class="ml-2 text-gray-50">{{ __('SSH Keys') }}</span>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('servers.services', ['server' => $server])" :active="request()->routeIs('servers.services')">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="ml-2 text-gray-50">{{ __('Services') }}</span>
                        </x-sidebar-link>
                    @endif
                    <x-sidebar-link :href="route('servers.settings', ['server' => $server])" :active="request()->routeIs('servers.settings')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                        <span class="ml-2 text-gray-50">{{ __('Settings') }}</span>
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('servers.logs', ['server' => $server])" :active="request()->routeIs('servers.logs')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
                        </svg>
                        <span class="ml-2 text-gray-50">{{ __('Logs') }}</span>
                    </x-sidebar-link>
                @endif
            </div>
        </div>

        @if (isset($sidebar))
            <div
                class="min-h-screen w-64 flex-none border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                {{ $sidebar }}
            </div>
        @endif

        <div class="flex min-h-screen flex-grow flex-col">
            <nav class="h-16 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-end">
                        <div class="flex items-center justify-center">
                            {{-- Search --}}
                        </div>
                        @include('layouts.partials.color-scheme')
                        <div class="ml-6 flex items-center">
                            <div class="relative ml-5">
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <div class="flex cursor-pointer items-center justify-between">
                                            {{ auth()->user()->name }}
                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('profile')">Profile</x-dropdown-link>
                                        <div class="border-t border-gray-100 dark:border-gray-700"></div>

                                        <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault(); this.closest('form').submit();">
                                                {{ __('Log Out') }}
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="mx-auto flex h-20 w-full max-w-full items-center justify-between px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            @if (isset($header2))
                <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="mx-auto max-w-full py-6 px-8">
                        {{ $header2 }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="px-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    <x-toast />
    <livewire:broadcast />
    @livewireScripts
    <script>
        document.addEventListener('livewire:load', () => {
            Livewire.onPageExpired((response, message) => {
                ({
                    href: window.location.href
                } = window.location);
            })
        })

        // On page load or when changing themes, best to add inline in `head` to avoid FOUC
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</body>

</html>
