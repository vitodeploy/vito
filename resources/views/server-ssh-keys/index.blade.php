<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("SSH Keys") }}</x-slot>

    <livewire:server-ssh-keys.server-keys-list :server="$server" />
</x-server-layout>
