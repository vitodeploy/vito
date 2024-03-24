<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Logs") }}</x-slot>

    @include("server-logs.partials.logs-list", ["server" => $site->server, "site" => $site])
</x-site-layout>
