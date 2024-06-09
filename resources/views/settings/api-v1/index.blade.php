<x-settings-layout>
    <x-slot name="pageTitle">{{ __("API Keys") }}</x-slot>

    @include("settings.api-v1.partials.apikeys-list")
</x-settings-layout>
