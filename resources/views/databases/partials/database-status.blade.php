@if ($status == \App\Enums\DatabaseStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DatabaseStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DatabaseStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DatabaseStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
