<?php

namespace App\Jobs\Component;

use App\Actions\Component\InstallComponent;
use App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Models\Component;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallComponentJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected int $componentId, protected int $serverId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->serverId}", fn () => app(InstallComponent::class)->install($this->componentId));
        $component = Component::query()->find($this->componentId);
        if ($component?->status === ComponentStatus::READY && $component->type === ComponentType::APP) {
            app(SyncEnvironmentResourceAccess::class)->dispatch($component->environment);
            app(DeployEnvironment::class)->join($component->environment);
        }
    }

    public function failed(Exception $e): void
    {
        app(InstallComponent::class)->failed($this->componentId);
    }
}
