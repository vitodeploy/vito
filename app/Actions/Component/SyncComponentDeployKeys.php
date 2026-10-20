<?php

namespace App\Actions\Component;

use App\Actions\SourceControl\SetupDeployKey;
use App\Enums\ComponentStatus;
use App\Models\Application;
use App\Models\Component;
use App\Models\SourceControl;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncComponentDeployKeys
{
    public function handle(int $componentId, ComponentStatus $expectedStatus = ComponentStatus::READY): void
    {
        $component = Component::query()->with('environment.application', 'server')->find($componentId);
        if (! $component || $component->status !== $expectedStatus) {
            return;
        }

        $application = $component->environment->application;
        $this->sync($component);

        DB::transaction(function () use ($component, $application, $expectedStatus): void {
            $current = Application::query()->lockForUpdate()->findOrFail($application->id);
            $component->refresh();
            if ($component->status !== $expectedStatus) {
                return;
            }
            if ($current->source_control_id !== $application->source_control_id || $current->repository !== $application->repository) {
                throw new RuntimeException('The application repository changed during key registration. Retry installation.');
            }
            if ($expectedStatus === ComponentStatus::READY) {
                $component->needs_redeploy = true;
            }
            $component->status = ComponentStatus::READY;
            $component->last_error = null;
            $component->save();
        });

        app(BroadcastComponentUpdate::class)->broadcast($component);
    }

    /**
     * Persist ownership before remote registration so even a lost response can be reconciled on retry or teardown.
     */
    public function sync(Component $component): void
    {
        $application = $component->environment->application;
        $sourceControl = app(\App\Actions\Application\GetApplicationSourceControl::class)->get($application);

        if ($component->deploy_key_source_control_id === $sourceControl->id
            && $component->deploy_key_repository === $application->repository) {
            if ($component->deploy_key_id !== null) {
                return;
            }
            $component->deploy_key_id = $sourceControl->provider()->findDeployKey($application->repository, $component->ssh_key ?? '');
            $component->save();
            if ($component->deploy_key_id !== null) {
                return;
            }
        } else {
            $this->remove($component);
        }

        if ($sourceControl->isGithubApp()) {
            if ($component->ssh_key !== null) {
                $this->removeLocal($component);
            }

            return;
        }

        if ($component->ssh_key === null) {
            $component->ssh_key = app(SetupDeployKey::class)->generateKey($component->server, $component->user, 'component_'.$component->id);
        }
        $component->deploy_key_source_control_id = $sourceControl->id;
        $component->deploy_key_repository = $application->repository;
        DB::transaction(function () use ($component): void {
            Component::query()->lockForUpdate()->findOrFail($component->id);
            $component->save();
        });

        $result = app(SetupDeployKey::class)->setup(
            $component->server, $component->user, 'component_'.$component->id, $sourceControl,
            $application->repository, 'component_'.$component->id, publicKey: $component->ssh_key, readOnly: true,
        );
        if ($result['deploy_key_id'] === null || $result['deploy_key_id'] === '') {
            throw new RuntimeException('The source control did not return a deploy key identifier.');
        }
        $component->deploy_key_id = $result['deploy_key_id'];
        $component->save();
    }

    public function remove(Component $component): void
    {
        if ($component->deploy_key_id !== null || $component->deploy_key_source_control_id !== null) {
            $this->removeProviderKey($component->deploy_key_source_control_id, $component->deploy_key_repository, $component->deploy_key_id, $component->ssh_key);
            $component->deploy_key_id = null;
            $component->deploy_key_source_control_id = null;
            $component->deploy_key_repository = null;
            $component->save();
        }
    }

    public function removeProviderKey(?int $sourceControlId, ?string $repository, ?string $keyId, ?string $publicKey = null): void
    {
        $sourceControl = SourceControl::withTrashed()->find($sourceControlId);
        if (! $sourceControl || $repository === null) {
            throw new RuntimeException('The deploy key source control is unavailable.');
        }

        $keyId ??= $sourceControl->provider()->findDeployKey($repository, $publicKey ?? '');
        if ($keyId !== null) {
            $sourceControl->provider()->deleteDeployKey($keyId, $repository);
        }
    }

    public function removeLocal(Component $component): void
    {
        $component->server->ssh()->exec(view('ssh.components.delete-key', ['key' => 'component_'.$component->id, 'user' => $component->user]), 'component-delete-key');
        $component->ssh_key = null;
        $component->save();
    }
}
