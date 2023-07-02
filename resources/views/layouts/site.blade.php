<x-app-layout>
    @if(isset($pageTitle))
        <x-slot name="pageTitle">{{ $site->domain }} - {{ $pageTitle }}</x-slot>
    @endif

    <x-container class="flex">
        @if($site->status == \App\Enums\SiteStatus::READY)
            <div class="hidden lg:block lg:flex-none w-64">
                <x-sidebar-link :href="route('servers.sites.show', ['server' => $site->server, 'site' => $site])" :active="request()->routeIs('servers.sites.show')">
                    <x-heroicon-o-home class="w-6 h-6 mr-1" />
                    {{ __('Overview') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites.application', ['server' => $site->server, 'site' => $site])" :active="request()->routeIs('servers.sites.application')">
                    <x-heroicon-o-window class="w-6 h-6 mr-1" />
                    {{ __('Application') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites.ssl', ['server' => $site->server, 'site' => $site])" :active="request()->routeIs('servers.sites.ssl')">
                    <x-heroicon-o-lock-closed class="w-6 h-6 mr-1" />
                    {{ __('SSL') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites.queues', ['server' => $site->server, 'site' => $site])" :active="request()->routeIs('servers.sites.queues')">
                    <x-heroicon-o-queue-list class="w-6 h-6 mr-1" />
                    {{ __('Queues') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites.settings', ['server' => $site->server, 'site' => $site])" :active="request()->routeIs('servers.sites.settings')">
                    <x-heroicon-o-cog-6-tooth class="w-6 h-6 mr-1" />
                    {{ __('Settings') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])" :active="request()->routeIs('servers.sites.logs')">
                    <x-heroicon-o-square-3-stack-3d class="w-6 h-6 mr-1" />
                    {{ __('Logs') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('servers.sites', ['server' => $site->server])">
                    <x-heroicon-o-arrow-left class="w-6 h-6 mr-1" />
                    {{ __('Go Back') }}
                </x-sidebar-link>
            </div>
        @endif

        <div class="w-full space-y-10">
            {{ $slot }}
        </div>
    </x-container>
</x-app-layout>
