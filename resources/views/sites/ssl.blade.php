<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("SSL") }}</x-slot>

    <livewire:ssl.ssls-list :site="$site" />
</x-site-layout>
