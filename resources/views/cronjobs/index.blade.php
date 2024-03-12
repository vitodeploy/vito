<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Cronjobs") }}</x-slot>

    @include("cronjobs.partials.cronjobs-list")
</x-server-layout>
