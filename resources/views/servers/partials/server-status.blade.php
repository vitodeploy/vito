<div>
    @if ($server->status == \App\Enums\ServerStatus::READY)
        <x-status status="success">{{ $server->status }}</x-status>
    @endif

    @if ($server->status == \App\Enums\ServerStatus::INSTALLING)
        <x-status status="warning">{{ $server->status }}</x-status>
    @endif

    @if ($server->status == \App\Enums\ServerStatus::DISCONNECTED)
        <x-status status="disabled">{{ $server->status }}</x-status>
    @endif

    @if ($server->status == \App\Enums\ServerStatus::INSTALLATION_FAILED)
        <x-status status="danger">{{ $server->status }}</x-status>
    @endif

    @if ($server->status == \App\Enums\ServerStatus::UPDATING)
        <x-status status="warning">{{ $server->status }}</x-status>
    @endif
</div>
