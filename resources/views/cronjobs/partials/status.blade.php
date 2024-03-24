@if ($status == \App\Enums\CronjobStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif
