<x-app-layout>
    <x-slot name="pageTitle">{{ $script->name }}</x-slot>

    @include("scripts.partials.script-executions-list")
</x-app-layout>
