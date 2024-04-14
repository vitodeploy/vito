<x-server-layout :server="$server">
    @if (isset($pageTitle))
        <x-slot name="pageTitle">{{ $pageTitle }}</x-slot>
    @endif

    @include("server-logs.partials.header")
    @include("server-logs.partials.logs-list-live")
</x-server-layout>
