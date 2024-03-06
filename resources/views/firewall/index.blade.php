<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Firewall") }}</x-slot>

    @include("firewall.partials.firewall-rules-list")
</x-server-layout>
