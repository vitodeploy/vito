<?php

namespace App\Actions\Component;

use App\Actions\ComponentCapability\InstallWebCapability;
use App\Actions\PHP\EnsureFpmPool;
use App\DTOs\ToolingTargetDTO;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Enums\WebMode;
use App\Models\Component;
use App\Tooling\ToolingRegistry;
use RuntimeException;

class SyncComponentRuntime
{
    public function handle(int $componentId): void
    {
        $component = Component::query()->with('environment.application')->find($componentId);
        if (! $component || ! in_array($component->status, [ComponentStatus::PENDING, ComponentStatus::INSTALLING], true)
            || $component->environment->status !== EnvironmentStatus::READY
            || $component->environment->application->status !== ApplicationStatus::READY) {
            return;
        }
        $this->sync($component);
        $component->refresh();
        if (in_array($component->status, [ComponentStatus::PENDING, ComponentStatus::INSTALLING], true)) {
            $component->status = ComponentStatus::READY;
            $component->last_error = null;
            $component->save();
            app(BroadcastComponentUpdate::class)->broadcast($component);
        }
    }

    public function sync(Component $component): void
    {
        $preset = $component->environment->application->preset();
        $version = $component->environment->runtime_version;
        $toolId = $preset->tooling();
        if ($toolId !== null) {
            $tool = ToolingRegistry::find($toolId) ?? throw new RuntimeException('The runtime tooling is unavailable.');
            $tool->install(ToolingTargetDTO::forComponent($component), $version ?? '');
        }

        if ($preset->webMode() === WebMode::FPM && $component->hasCapability('web')) {
            if ($component->user !== $component->server->getSshUser()) {
                app(EnsureFpmPool::class)->ensure($component->server, $component->user, $version ?? '');
            }
            if ($component->installed_runtime_version !== null && $component->installed_runtime_version !== $version) {
                app(InstallWebCapability::class)->removePool($component, $component->installed_runtime_version);
            }
        }
        $component->installed_runtime_version = $version;
        $component->save();
    }
}
