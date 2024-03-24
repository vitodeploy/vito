<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Sites") }}</x-slot>

    @include("sites.partials.sites-list")
</x-server-layout>
