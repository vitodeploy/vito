<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Cronjobs") }}</x-slot>

    @include("cronjobs.partrials.cronjobs-list")
</x-server-layout>
