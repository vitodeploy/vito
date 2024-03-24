@if ($status == \App\Enums\DatabaseUserStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DatabaseUserStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DatabaseUserStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DatabaseUserStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
