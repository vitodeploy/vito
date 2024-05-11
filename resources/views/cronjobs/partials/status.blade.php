@if ($status == \App\Enums\CronjobStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::DISABLING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::DISABLED)
    <x-status status="disabled">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::ENABLING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\CronjobStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif
