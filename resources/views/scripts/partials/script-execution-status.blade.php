@if ($status == \App\Enums\ScriptExecutionStatus::EXECUTING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ScriptExecutionStatus::COMPLETED)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\ScriptExecutionStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
