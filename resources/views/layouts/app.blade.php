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

        @include("layouts.partials.favicon")

        <!-- Scripts -->
        @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>

    <body class="bg-gray-50 font-sans antialiased dark:bg-gray-900 dark:text-gray-300" x-data="" x-cloak>
        @include("layouts.partials.search")

        @include("layouts.navigation")

        @include("layouts.sidebar")

        <div class="mt-[64px] w-full"></div>

        <div class="sm:ml-64">
            @if (isset($header))
                <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="mx-auto flex h-20 w-full max-w-full items-center justify-between px-5">
                        {{ $header }}
                    </div>
                </header>
            @endif

            @if (isset($header2))
                <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="mx-auto max-w-full px-5 py-6">
                        {{ $header2 }}
                    </div>
                </header>
            @endif

            <div class="px-4 py-10">
                {{ $slot }}
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
        @stack("footer")
    </body>
</html>
