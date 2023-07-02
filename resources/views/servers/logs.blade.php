<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }} Logs</x-slot>

    <livewire:server-logs.logs-list :server="$server"/>
</x-server-layout>
