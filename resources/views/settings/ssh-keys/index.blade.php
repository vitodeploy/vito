<x-settings-layout>
    <x-slot name="pageTitle">{{ __("SSH Keys") }}</x-slot>

    @include("settings.ssh-keys.partials.keys-list")
</x-settings-layout>
