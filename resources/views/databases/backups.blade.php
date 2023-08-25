<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Backup Files") }}</x-slot>

    <div class="space-y-10">
        <livewire:databases.database-backup-files :server="$server" :backup="$backup" />
    </div>
</x-server-layout>
