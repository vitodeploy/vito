@if ($status == \App\Enums\BackupFileStatus::CREATED)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupFileStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupFileStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupFileStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupFileStatus::RESTORING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupFileStatus::RESTORED)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\BackupFileStatus::RESTORE_FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
