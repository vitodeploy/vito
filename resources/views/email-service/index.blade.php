<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Email Service Manager") }}</x-slot>

    @include("email-service.partials.email-list")

</x-server-layout>
