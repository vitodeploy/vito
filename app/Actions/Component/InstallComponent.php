<?php

namespace App\Actions\Component;

use App\Actions\IsolatedUser\EnsureIsolatedUser;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Component;
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

        if ($component->type === \App\Enums\ComponentType::LOAD_BALANCER) {
            app(SyncComponentVhost::class)->sync($component);
            DB::transaction(function () use ($component): void {
                Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
                $component->refresh();
                if ($component->status === ComponentStatus::INSTALLING) {
                    $component->status = ComponentStatus::READY;
                    $component->save();
                }
            });
            app(BroadcastComponentUpdate::class)->broadcast($component);

            return;
        }

        if ($component->isolatedUser) {
            app(EnsureIsolatedUser::class)->ensure($component->server, $component->user);
        }

        $component->server->ssh($component->user)->exec(view('ssh.components.create-directories', ['component' => $component]), 'component-directories');

        app(SyncComponentRuntime::class)->sync($component);
        $application = $component->environment->application;
        app(SyncComponentDeployKeys::class)->sync($component);
        app(SyncComponentCapabilities::class)->sync($component);

        DB::transaction(function () use ($component, $application): void {
            $current = Application::query()->lockForUpdate()->findOrFail($application->id);
            $component->refresh();
            if ($component->status !== ComponentStatus::INSTALLING) {
                return;
            }
            if ($current->source_control_id !== $application->source_control_id || $current->repository !== $application->repository) {
                throw new RuntimeException('The application repository changed during installation. Retry installation.');
            }
            $component->status = ComponentStatus::READY;
            $component->last_error = null;
            $component->save();
        });
        app(BroadcastComponentUpdate::class)->broadcast($component);
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
