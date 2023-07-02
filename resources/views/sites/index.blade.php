<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Sites") }}</x-slot>

    <livewire:sites.sites-list :server="$server" />
</x-server-layout>
