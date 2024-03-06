@if ($status == \App\Enums\SslStatus::CREATED)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SslStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SslStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SslStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
