<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("SSH Keys") }}</x-slot>

    @include("server-ssh-keys.partials.server-keys-list")
</x-server-layout>
