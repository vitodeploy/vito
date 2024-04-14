<div class="space-y-6">
    <x-live id="live-server">
        @if ($server->status === \App\Enums\ServerStatus::INSTALLING)
            @include("servers.partials.installing", ["server" => $server])
        @endif

        @if ($server->status === \App\Enums\ServerStatus::INSTALLATION_FAILED)
            @include("servers.partials.installation-failed", ["server" => $server])
        @endif

        @if (in_array($server->status, [\App\Enums\ServerStatus::READY, \App\Enums\ServerStatus::DISCONNECTED]))
            <div class="space-y-10">
                @include("servers.partials.server-overview", ["server" => $server])
            </div>
        @endif
    </x-live>

    @include("server-logs.partials.logs-list-live", ["pageTitle" => "Logs"])
</div>
