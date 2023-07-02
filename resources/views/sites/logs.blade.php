<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Logs") }}</x-slot>

    <livewire:server-logs.logs-list :server="$site->server" :site="$site" />
</x-site-layout>
