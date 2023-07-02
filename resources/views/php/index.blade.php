<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("PHP") }}</x-slot>

    <livewire:php.installed-versions :server="$server" />

    @if($server->defaultService('php'))
        <livewire:php.default-cli :server="$server"/>
    @endif
</x-server-layout>
