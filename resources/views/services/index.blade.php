<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Services") }}</x-slot>

    <livewire:services.services-list :server="$server" />
</x-server-layout>
