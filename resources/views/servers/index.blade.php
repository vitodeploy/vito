<x-app-layout>
    <x-slot name="pageTitle">{{ __("Servers") }}</x-slot>

    @include("servers.partials.servers-list")
</x-app-layout>
