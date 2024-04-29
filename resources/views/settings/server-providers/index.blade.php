<x-settings-layout>
    <x-slot name="pageTitle">{{ __("Storage Providers") }}</x-slot>

    @include("settings.server-providers.partials.providers-list")
</x-settings-layout>
