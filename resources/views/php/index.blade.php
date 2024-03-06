<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("PHP") }}</x-slot>

    @include("php.partials.installed-versions")

    @if ($server->defaultService("php"))
        @include("php.partials.default-cli")
    @endif
</x-server-layout>
