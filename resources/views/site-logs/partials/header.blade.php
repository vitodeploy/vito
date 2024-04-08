<x-card-header>
    <x-slot name="title">{{ __("Logs") }}</x-slot>
    <x-slot name="description">
        {{ __('View all logs associated with ":site"', ['site' => $site->domain]) }}
    </x-slot>
    <x-slot name="aside">

            <x-secondary-button
                icon="o-square-3-stack-3d"
                :active="!isset($remote)"
                x-data=""
                class="mr-1"
                :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])"
            >
                {{ __("Vito Logs") }}
            </x-secondary-button>

            <x-secondary-button
                icon="o-document-magnifying-glass"
                :active="isset($remote)"
                x-data=""
                :href="route('servers.sites.logs.remote', ['server' => $site->server, 'site' => $site])"
            >
                {{ __("Remote Logs") }}
            </x-secondary-button>

    </x-slot>
</x-card-header>
