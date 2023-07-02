<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Settings") }}</x-slot>

    <livewire:server-settings.server-details :server="$server" />

    <x-card>
        <x-slot name="title">{{ __("Public Key") }}</x-slot>
        <x-slot name="description">{{ __("Your server's public key is here") }}</x-slot>
        <div class="mt-5">
            <div>
                <div class="flex items-center justify-between">
                    <x-input-label for="pk">
                        {{ __("Public Key") }}
                    </x-input-label>
                    <x-input-label class="cursor-pointer" x-data="{ copied: false }" x-clipboard.raw="{{ $server->public_key }}">
                        <div x-show="copied" class="flex items-center">
                            {{ __("Copied") }}
                            <x-heroicon-m-check class="ml-1 w-4 text-green-700" />
                        </div>
                        <div x-show="!copied" x-on:click="copied = true; setTimeout(() => {copied = false}, 2000)">{{ __("Copy") }}</div>
                    </x-input-label>
                </div>
                <x-textarea id="pk" name="pk" class="mt-1 h-48" disabled>{{ $server->public_key }}</x-textarea>
            </div>
        </div>
    </x-card>

    <livewire:server-settings.edit-server :server="$server" />

    <x-card>
        <x-slot name="title">{{ __("Delete Server") }}</x-slot>
        <x-slot name="description">{{ __("Permanently delete the server.") }}</x-slot>
        <p>{{ __("Once your server is deleted, all of its resources and data will be permanently deleted and can't be restored") }}</p>
        <div class="mt-5">
            <livewire:servers.delete-server :server="$server"/>
        </div>
    </x-card>
</x-server-layout>
