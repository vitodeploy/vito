<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @if(isset($pageTitle))
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
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 dark:text-gray-300 min-h-screen">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
    <x-toast />
    @livewireScripts
    <script>
        document.addEventListener('livewire:load', () => {
            Livewire.onPageExpired((response, message) => {
                ({href: window.location.href} = window.location);
            })
        })
    </script>
</body>
</html>
