<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ $site->domain }}</x-slot>

    @include("application." . $site->type . "-app", ["site" => $site])
</x-site-layout>
