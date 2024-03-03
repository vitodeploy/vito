@if ($status == \App\Enums\DeploymentStatus::DEPLOYING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DeploymentStatus::FINISHED)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\DeploymentStatus::FAILED)
    <x-status status="danger">{{ $status }}</x-status>
@endif
