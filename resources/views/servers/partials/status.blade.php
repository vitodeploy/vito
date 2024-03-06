@if ($status == \App\Enums\ServerStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServerStatus::INSTALLING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServerStatus::DISCONNECTED)
    <x-status status="disabled">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServerStatus::INSTALLATION_FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
