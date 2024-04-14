<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ $pageTitle }}</x-slot>

    @include("server-logs.partials.logs-list-live", ["server" => $site->server, "site" => $site])
</x-site-layout>
