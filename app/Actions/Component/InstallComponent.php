<?php

namespace App\Actions\Component;

use App\Actions\IsolatedUser\EnsureIsolatedUser;
use App\DTOs\ToolingTargetDTO;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Component;
use App\Tooling\ToolingRegistry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstallComponent
{
    public function install(int $componentId): void
    {
        $component = Component::query()->with('environment.application', 'server', 'isolatedUser')->find($componentId);
        if (! $component) {
            return;
        }

        $install = DB::transaction(function () use ($component): bool {
            Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
            $component->refresh();
            if (! in_array($component->status, [ComponentStatus::PENDING, ComponentStatus::INSTALLING], true)
                || $component->environment->status !== EnvironmentStatus::READY
                || $component->environment->application->status !== ApplicationStatus::READY) {
                return false;
            }

            $component->status = ComponentStatus::INSTALLING;
            $component->last_error = null;
            $component->save();

            return true;
        });
        if (! $install) {
            return;
        }
        app(BroadcastComponentUpdate::class)->broadcast($component);

        if ($component->isolatedUser) {
            app(EnsureIsolatedUser::class)->ensure($component->server, $component->user);
        }

        $component->server->ssh($component->user)->exec(view('ssh.components.create-directories', ['component' => $component]), 'component-directories');

        $toolId = $component->environment->application->preset()->tooling();
        if ($toolId !== null) {
            $tool = ToolingRegistry::find($toolId) ?? throw new RuntimeException('The runtime tooling is unavailable.');
            $tool->install(new ToolingTargetDTO(
                server: $component->server,
                user: $component->user,
                isolatedUser: $component->isolatedUser,
                phpVersion: $component->environment->runtime_version,
                configDir: $component->path,
            ), $component->environment->runtime_version ?? '');
        }

        app(SyncComponentDeployKeys::class)->handle($componentId, ComponentStatus::INSTALLING);
    }

    public function failed(int $componentId): void
    {
        $component = Component::query()->find($componentId);
        if (! $component || in_array($component->status, [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED], true)) {
            return;
        }

        $component->status = ComponentStatus::INSTALLATION_FAILED;
        $component->last_error = 'Component installation failed. Check server connectivity, required services and repository access, then retry.';
        $component->save();
        app(BroadcastComponentUpdate::class)->broadcast($component);
    }
}
