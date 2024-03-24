<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }} Logs</x-slot>

    @include("server-logs.partials.logs-list")
</x-server-layout>
