<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ $server->name }}</x-slot>

    @include("servers.partials.show-server")
</x-server-layout>
