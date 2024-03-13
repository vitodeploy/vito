@if ($status == \App\Enums\QueueStatus::RUNNING)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::RESTARTING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::STARTING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::STOPPING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::STOPPED)
    <x-status status="danger">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\QueueStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif
