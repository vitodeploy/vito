<x-app-layout>
    @if(isset($pageTitle))
        <x-slot name="pageTitle">{{ $pageTitle }} - {{ $server->name }}</x-slot>
    @endif

    <x-container class="flex">
        @if(in_array($server->status, [\App\Enums\ServerStatus::READY, \App\Enums\ServerStatus::DISCONNECTED]))
            <div class="hidden lg:block lg:flex-none w-64">
                <x-sidebar-link :href="route('servers.show', ['server' => $server])" :active="request()->routeIs('servers.show')">
                    <x-heroicon-o-home class="w-6 h-6 mr-1" />
                    {{ __('Overview') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites', ['server' => $server])" :active="request()->routeIs('servers.sites') || request()->is('servers/*/sites/*')">
                    <x-heroicon-o-globe-alt class="w-6 h-6 mr-1" />
                    {{ __('Sites') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.databases', ['server' => $server])" :active="request()->routeIs('servers.databases')">
                    <x-heroicon-o-circle-stack class="w-6 h-6 mr-1" />
                    {{ __('Databases') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.php', ['server' => $server])" :active="request()->routeIs('servers.php')">
                    <x-heroicon-o-code-bracket class="w-6 h-6 mr-1" />
                    {{ __('PHP') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.firewall', ['server' => $server])" :active="request()->routeIs('servers.firewall')">
                    <x-heroicon-o-fire class="w-6 h-6 mr-1" />
                    {{ __('Firewall') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.cronjobs', ['server' => $server])" :active="request()->routeIs('servers.cronjobs')">
                    <x-heroicon-o-clock class="w-6 h-6 mr-1" />
                    {{ __('Cronjobs') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.ssh-keys', ['server' => $server])" :active="request()->routeIs('servers.ssh-keys')">
                    <x-heroicon-o-key class="w-6 h-6 mr-1" />
                    {{ __('SSH Keys') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.services', ['server' => $server])" :active="request()->routeIs('servers.services')">
                    <x-heroicon-o-cog class="w-6 h-6 mr-1" />
                    {{ __('Services') }}
                </x-sidebar-link>
                {{--<x-sidebar-link :href="route('servers.daemons', ['server' => $server])" :active="request()->routeIs('servers.daemons')">--}}
                {{--    <x-heroicon-o-queue-list class="w-6 h-6 mr-1" />--}}
                {{--    {{ __('Daemons') }}--}}
                {{--</x-sidebar-link>--}}
                <x-sidebar-link :href="route('servers.settings', ['server' => $server])" :active="request()->routeIs('servers.settings')">
                    <x-heroicon-o-cog-6-tooth class="w-6 h-6 mr-1" />
                    {{ __('Settings') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.logs', ['server' => $server])" :active="request()->routeIs('servers.logs')">
                    <x-heroicon-o-square-3-stack-3d class="w-6 h-6 mr-1" />
                    {{ __('Logs') }}
                </x-sidebar-link>
            </div>
        @endif

        <div class="w-full space-y-10">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
