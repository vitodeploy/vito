<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Create Site") }}</x-slot>

    @include("sites.partials.create-site")
</x-server-layout>
