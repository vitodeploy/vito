<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Create Site") }}</x-slot>

    <livewire:sites.create-site :server="$server" />
</x-server-layout>
