<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("SSL") }}</x-slot>

    @include("ssls.partials.ssls-list")
</x-site-layout>
