@if ($status == \App\Enums\ServiceStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::INSTALLING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::INSTALLATION_FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::UNINSTALLING)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::RESTARTING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::STARTING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::STOPPING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ServiceStatus::STOPPED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
