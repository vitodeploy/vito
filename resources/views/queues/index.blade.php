<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Queues") }}</x-slot>

    @include("queues.partials.queues-list")
</x-site-layout>
