<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ $site->domain }}</x-slot>

    <x-live id="site">
        @if ($site->status === \App\Enums\SiteStatus::INSTALLING)
            @include("sites.partials.installing", ["site" => $site])
        @endif

        @if ($site->status === \App\Enums\SiteStatus::INSTALLATION_FAILED)
            @include("sites.partials.installation-failed", ["site" => $site])
        @endif
    </x-live>

    @include("server-logs.partials.logs-list-live", ["server" => $site->server, "site" => $site])
</x-site-layout>
