@if ($status == \App\Enums\SiteStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SiteStatus::INSTALLING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SiteStatus::INSTALLATION_FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SiteStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif
