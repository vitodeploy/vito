<x-app-layout>
    <x-slot name="pageTitle">Admin - Users</x-slot>

    <x-container>
        @include("admin.users.partials.update-user-info")

        @include("admin.users.partials.update-projects")
    </x-container>
</x-app-layout>
