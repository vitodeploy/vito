@if ($status == \App\Enums\BackupStatus::RUNNING)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
