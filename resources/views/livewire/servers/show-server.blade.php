<div>
    @if($server->status === \App\Enums\ServerStatus::INSTALLING)
        @include('livewire.servers.partials.installing', ['server' => $server])
        <livewire:server-logs.logs-list :server="$server" />
    @endif
    @if($server->status === \App\Enums\ServerStatus::INSTALLATION_FAILED)
        @include('livewire.servers.partials.installation-failed', ['server' => $server])
        <livewire:server-logs.logs-list :server="$server" />
    @endif
    @if(in_array($server->status, [\App\Enums\ServerStatus::READY, \App\Enums\ServerStatus::DISCONNECTED]))
        <div class="space-y-10">
            @include('livewire.servers.partials.server-overview', ['server' => $server])
            <livewire:server-logs.logs-list :server="$server" :count="10" />
        </div>
    @endif
</div>
