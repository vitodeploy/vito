<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Firewall") }}</x-slot>

    <livewire:firewall.firewall-rules-list :server="$server" />
</x-server-layout>
