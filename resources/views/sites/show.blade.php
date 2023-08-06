<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ $site->domain }}</x-slot>

    <livewire:sites.show-site :site="$site" />
</x-site-layout>
