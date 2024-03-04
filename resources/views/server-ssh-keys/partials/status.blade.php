@if ($status == \App\Enums\SshKeyStatus::ADDED)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SshKeyStatus::ADDING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\SshKeyStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif
