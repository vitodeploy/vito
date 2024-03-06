<div>
    @if ($site->status === \App\Enums\SiteStatus::INSTALLING)
        @include("sites.partials.installing", ["site" => $site])

        @include("server-logs.partials.logs-list", ["server" => $site->server, "site" => $site])
    @endif

    @if ($site->status === \App\Enums\SiteStatus::INSTALLATION_FAILED)
        @include("sites.partials.installation-failed", ["site" => $site])

        @include("server-logs.partials.logs-list", ["server" => $site->server, "site" => $site])
    @endif

    @if ($site->status === \App\Enums\SiteStatus::READY)
        @include("application." . $site->type . "-app", ["site" => $site])
    @endif
</div>
