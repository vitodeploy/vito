<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config("app.name", "Laravel") }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(["resources/css/app.css", "resources/js/app.js"])

        @include("layouts.partials.favicon")
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div
            class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-900 sm:justify-center sm:pt-0"
        >
            <div>
                <a href="/">
                    <div class="flex items-center justify-start text-3xl font-extrabold">
                        <x-application-logo class="h-9 w-9 rounded-md" />
                        <span class="ml-1">Deploy</span>
                    </div>
                </a>
            </div>

            <div
                class="mt-10 w-full overflow-hidden rounded-lg bg-white px-6 py-4 shadow-md dark:bg-gray-800 sm:max-w-md"
            >
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
