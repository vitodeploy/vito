<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Databases") }}</x-slot>

    <div class="space-y-10">
        <livewire:databases.database-list :server="$server" />

        <livewire:databases.database-user-list :server="$server" />

        <livewire:databases.database-backups :server="$server" />
    </div>
</x-server-layout>
