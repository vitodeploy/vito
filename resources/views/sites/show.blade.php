<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ $site->domain }}</x-slot>

    <livewire:sites.show-site :site="$site" />

    <livewire:server-logs.logs-list :server="$site->server" :site="$site" :count="10" />
</x-site-layout>
