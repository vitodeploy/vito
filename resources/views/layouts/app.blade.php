@php
    use App\Enums\ServerStatus;
@endphp

@props([
    "server",
])
<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" hx-swap-oob="true" />

        <title>
            @if (isset($pageTitle))  {{ $pageTitle }} -
            @endif {{ config("app.name", "Laravel") }}
        </title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script src="{{ asset("static/libs/ace/ace.js") }}"></script>
        <script src="{{ asset("static/libs/ace/theme-github.js") }}"></script>
        <script src="{{ asset("static/libs/ace/theme-one-dark.js") }}"></script>
        <script src="{{ asset("static/libs/ace/mode-sh.js") }}"></script>

        @include("layouts.partials.favicon")

        <!-- Scripts -->
        @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>

    <body
        class="min-h-screen min-w-max bg-gray-100 font-sans antialiased dark:bg-gray-900 dark:text-gray-300"
        x-data=""
        x-cloak
    >
        <div class="flex min-h-screen">
            <div
                class="left-0 top-0 min-h-screen w-64 flex-none bg-gray-800 p-3 dark:border-r-2 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <div class="block h-16">
                    <div class="flex items-center justify-start text-2xl font-extrabold text-white">
                        <x-application-logo class="h-7 w-7 rounded-md" />
                        <span class="ml-1">Deploy</span>
                    </div>
                </div>

                <div class="mb-5">
                    <div class="text-sm font-semibold uppercase text-gray-300">
                        {{ __("Projects") }}
                    </div>
                    <div class="mt-2">
                        @include("layouts.partials.project-select", ["project" => auth()->user()->currentProject])
                    </div>

                    <div class="mt-5 text-sm font-semibold uppercase text-gray-300">
                        {{ __("Servers") }}
                    </div>
                    <div class="mt-2">
                        @include("layouts.partials.server-select", ["server" => $server ?? null])
                    </div>

                    @if (isset($server))
                        <div class="mt-3 space-y-1">
                            <x-sidebar-link
                                :href="route('servers.show', ['server' => $server])"
                                :active="request()->routeIs('servers.show')"
                            >
                                <x-heroicon-o-home class="h-6 w-6" />
                                <span class="ml-2 text-gray-50">
                                    {{ __("Overview") }}
                                </span>
                            </x-sidebar-link>
                            @if ($server->webserver())
                                <x-sidebar-link
                                    :href="route('servers.sites', ['server' => $server])"
                                    :active="request()->routeIs('servers.sites') || request()->is('servers/*/sites/*')"
                                >
                                    <x-heroicon-o-globe-alt class="h-6 w-6" />
                                    <span class="ml-2 text-gray-50">
                                        {{ __("Sites") }}
                                    </span>
                                </x-sidebar-link>
                            @endif

                            @if ($server->database())
                                <x-sidebar-link
                                    :href="route('servers.databases', ['server' => $server])"
                                    :active="request()->routeIs('servers.databases') ||
                                    request()->routeIs('servers.databases.backups')"
                                >
                                    <x-heroicon-o-circle-stack class="h-6 w-6" />
                                    <span class="ml-2 text-gray-50">
                                        {{ __("Databases") }}
                                    </span>
                                </x-sidebar-link>
                            @endif

                            @if ($server->php())
                                <x-sidebar-link
                                    :href="route('servers.php', ['server' => $server])"
                                    :active="request()->routeIs('servers.php')"
                                >
                                    <x-heroicon-o-code-bracket class="h-6 w-6" />
                                    <span class="ml-2 text-gray-50">
                                        {{ __("PHP") }}
                                    </span>
                                </x-sidebar-link>
                            @endif

                            @if ($server->firewall())
                                <x-sidebar-link
                                    :href="route('servers.firewall', ['server' => $server])"
                                    :active="request()->routeIs('servers.firewall')"
                                >
                                    <x-heroicon-o-fire class="h-6 w-6" />
                                    <span class="ml-2 text-gray-50">
                                        {{ __("Firewall") }}
                                    </span>
                                </x-sidebar-link>
                            @endif

                            <x-sidebar-link
                                :href="route('servers.cronjobs', ['server' => $server])"
                                :active="request()->routeIs('servers.cronjobs')"
                            >
                                <x-heroicon-o-clock class="h-6 w-6" />
                                <span class="ml-2 text-gray-50">
                                    {{ __("Cronjobs") }}
                                </span>
                            </x-sidebar-link>
                            <x-sidebar-link
                                :href="route('servers.ssh-keys', ['server' => $server])"
                                :active="request()->routeIs('servers.ssh-keys')"
                            >
                                <x-heroicon-o-key class="h-6 w-6" />
                                <span class="ml-2 text-gray-50">
                                    {{ __("SSH Keys") }}
                                </span>
                            </x-sidebar-link>
                            <x-sidebar-link
                                :href="route('servers.services', ['server' => $server])"
                                :active="request()->routeIs('servers.services')"
                            >
                                <x-heroicon-o-cog-6-tooth class="h-6 w-6" />
                                <span class="ml-2 text-gray-50">
                                    {{ __("Services") }}
                                </span>
                            </x-sidebar-link>

                            <x-sidebar-link
                                :href="route('servers.settings', ['server' => $server])"
                                :active="request()->routeIs('servers.settings')"
                            >
                                <x-heroicon-o-wrench-screwdriver class="h-6 w-6" />
                                <span class="ml-2 text-gray-50">
                                    {{ __("Settings") }}
                                </span>
                            </x-sidebar-link>
                            <x-sidebar-link
                                :href="route('servers.logs', ['server' => $server])"
                                :active="request()->routeIs('servers.logs')"
                            >
                                <x-heroicon-o-square-3-stack-3d class="h-6 w-6" />
                                <span class="ml-2 text-gray-50">
                                    {{ __("Logs") }}
                                </span>
                            </x-sidebar-link>
                        </div>
                    @endif
                </div>
            </div>

            @if (isset($sidebar))
                <div
                    class="min-h-screen w-64 flex-none border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
                >
                    {{ $sidebar }}
                </div>
            @endif

            <div class="flex min-h-screen flex-grow flex-col">
                @include("layouts.navigation")

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
                        <div class="mx-auto max-w-full px-8 py-6">
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
        <script>
            // On page load or when changing themes, best to add inline in `head` to avoid FOUC
            if (
                localStorage.theme === 'dark' ||
                (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
            ) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        <x-toast />
        <x-htmx-error-handler />
    </body>
</html>
