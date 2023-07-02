<x-app-layout>
    @if(isset($pageTitle))
        <x-slot name="pageTitle">{{ $pageTitle }}</x-slot>
    @endif

    <x-container class="flex">
        <div class="hidden lg:block lg:flex-none w-64">
            <x-sidebar-link :href="route('profile')" :active="request()->routeIs('profile')">
                <x-heroicon-o-user class="w-6 h-6 mr-1" />
                {{ __('Profile') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('server-providers')" :active="request()->routeIs('server-providers')">
                <x-heroicon-o-server-stack class="w-6 h-6 mr-1" />
                {{ __('Server Providers') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('source-controls')" :active="request()->routeIs('source-controls')">
                <x-heroicon-o-code-bracket class="w-6 h-6 mr-1" />
                {{ __('Source Controls') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('notification-channels')" :active="request()->routeIs('notification-channels')">
                <x-heroicon-o-bell class="w-6 h-6 mr-1" />
                {{ __('Notification Channels') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('ssh-keys')" :active="request()->routeIs('ssh-keys')">
                <x-heroicon-o-key class="w-6 h-6 mr-1" />
                {{ __('SSH Keys') }}
            </x-sidebar-link>
        </div>

        <div class="w-full">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
