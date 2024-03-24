<x-app-layout>
    @if (isset($pageTitle))
        <x-slot name="pageTitle">{{ $pageTitle }}</x-slot>
    @endif

    <x-container class="flex">
        <div class="w-full">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
