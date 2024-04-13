<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Services") }}</x-slot>

    @include("services.partials.services-list")

    @include("services.partials.available-services")

    @stack("modals")
</x-server-layout>
