<x-profile-layout>
    <x-slot name="pageTitle">{{ __("Source Controls") }}</x-slot>

    <div>
        <x-card-header>
            <x-slot name="title">Source Controls</x-slot>
            <x-slot name="description">You can connect your source controls via API Tokens</x-slot>
        </x-card-header>

        <div class="space-y-3">
            @if(session('status') == 'not-connected')
                <div class="bg-red-100 px-4 py-2 rounded-lg text-red-600">{{ session('message') }}</div>
            @endif
            <livewire:source-controls.github />
            <livewire:source-controls.gitlab />
            <livewire:source-controls.bitbucket />
        </div>
    </div>

</x-profile-layout>
