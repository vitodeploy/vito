<x-server-layout :server="$server">
    <x-slot name="pageTitle">{{ __("Databases") }}</x-slot>

    <div class="space-y-10">
        @include("databases.partials.database-list")

        @include("databases.partials.database-user-list")

        @include("databases.partials.database-backups")
    </div>
</x-server-layout>
