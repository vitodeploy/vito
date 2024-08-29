<x-app-layout :server="$server">
    @if (isset($pageTitle))
        <x-slot name="pageTitle">{{ $pageTitle }} - {{ $server->name }}</x-slot>
    @endif

    <x-slot name="header">
        @if (isset($header))
            <header class="flex-grow border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto flex h-20 w-full max-w-full items-center justify-between">
                    {{ $header }}
                </div>
            </header>
        @else
            <div class="flex items-center">
                <h2 class="text-lg font-semibold">{{ $server->name }}</h2>
                <div class="ml-2">
                    @include("settings.tags.tags", ["taggable" => $server])
                </div>
            </div>
        @endif

        <div class="flex flex-col items-end">
            @include("servers.partials.server-status")
            <x-input-label class="mt-1 cursor-pointer" x-data="{ copied: false }">
                <div
                    class="flex items-center text-sm"
                    x-on:click="
                        window.copyToClipboard('{{ $server->ip }}')
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
                    {{ $server->ip }}
                </div>
            </x-input-label>
        </div>
    </x-slot>

    @if (isset($sidebar))
        <x-slot name="sidebar">
            {{ $sidebar }}
        </x-slot>
    @endif

    <x-container class="flex">
        <div class="w-full space-y-10">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
