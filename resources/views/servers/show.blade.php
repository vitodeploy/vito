<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }}</x-slot>

    <livewire:servers.show-server :server="$server" />
</x-server-layout>
