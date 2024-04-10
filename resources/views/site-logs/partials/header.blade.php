<x-card-header>
    <x-slot name="title">{{ __("Logs") }}</x-slot>
    <x-slot name="description">
        {{ __('View all logs associated with ":site"', ["site" => $site->domain]) }}
    </x-slot>
    <x-slot name="aside">
        <x-secondary-button
            :active="!isset($remote)"
            x-data=""
            class="flex items-center gap-1 mr-1"
            :href="route('servers.sites.logs', ['server' => $site->server, 'site' => $site])"
        >
            <x-heroicon name="o-square-3-stack-3d" class="h-5 w-5"/>
            {{ __("Vito Logs") }}
        </x-secondary-button>

        <x-secondary-button
            :active="isset($remote)"
            x-data=""
            class="flex items-center gap-1 mr-1"
            :href="route('servers.sites.logs.remote', ['server' => $site->server, 'site' => $site])"
        >
            <x-heroicon name="o-document-magnifying-glass" class="h-5 w-5"/>
            {{ __("Remote Logs") }}
        </x-secondary-button>
    </x-slot>
</x-card-header>
