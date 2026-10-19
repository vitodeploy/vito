<?php

namespace App\Jobs\Component;

use App\Actions\Component\InstallComponent;
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
        $component = \App\Models\Component::query()->find($this->componentId);
        if ($component?->status === \App\Enums\ComponentStatus::READY && $component->type === \App\Enums\ComponentType::APP) {
            app(\App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess::class)->dispatch($component->environment);
            app(\App\Actions\EnvironmentDeployment\DeployEnvironment::class)->join($component->environment);
        }
    }

    public function failed(Exception $e): void
    {
        app(InstallComponent::class)->failed($this->componentId);
    }
}
