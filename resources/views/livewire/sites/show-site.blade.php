<div>
    @if ($site->status === \App\Enums\SiteStatus::INSTALLING)
        @include("livewire.sites.partials.installing", ["site" => $site])

        <livewire:server-logs.logs-list
            :server="$site->server"
            :site="$site"
            :count="10"
        />
    @endif

    @if ($site->status === \App\Enums\SiteStatus::INSTALLATION_FAILED)
        @include("livewire.sites.partials.installation-failed", ["site" => $site])

        <livewire:server-logs.logs-list
            :server="$site->server"
            :site="$site"
            :count="10"
        />
    @endif

    @if ($site->status === \App\Enums\SiteStatus::READY)
        @livewire("application." . $site->type . "-app", ["site" => $site])
    @endif
</div>
