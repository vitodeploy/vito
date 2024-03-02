<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Backup Files") }}</x-slot>

    <div class="space-y-10">
        @include("databases.partials.database-backup-files")
    </div>
</x-server-layout>
