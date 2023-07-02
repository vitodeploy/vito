<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Cronjobs") }}</x-slot>

    <livewire:cronjobs.cronjobs-list :server="$server"/>
</x-server-layout>
