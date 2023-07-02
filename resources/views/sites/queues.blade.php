<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Queues") }}</x-slot>

    <livewire:queues.queues-list :site="$site" />
</x-site-layout>
