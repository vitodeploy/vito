<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("PHP") }}</x-slot>

    @error("version")
        <x-alert-danger>
            <x-input-error :messages="$errors->get('version')" />
        </x-alert-danger>
    @enderror

    @include("php.partials.installed-versions")

    @if ($server->defaultService("php"))
        @include("php.partials.default-cli")
    @endif
</x-server-layout>
