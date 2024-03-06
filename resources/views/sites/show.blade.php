<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ $site->domain }}</x-slot>

    @include("sites.partials.show-site")
</x-site-layout>
