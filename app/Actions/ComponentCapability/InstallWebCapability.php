<?php

namespace App\Actions\ComponentCapability;

use App\Actions\PHP\EnsureFpmPool;
use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\DeleteWorker;
use App\Actions\Worker\InstallWorker;
use App\Enums\WebMode;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Worker;
use App\Services\PHP\PHP;

class InstallWebCapability
{
    public function install(ComponentCapability $capability, Component $component): void
    {
        $mode = $component->environment->application->preset()->webMode();
        if ($mode === WebMode::FPM && $component->user !== $component->server->getSshUser()) {
            app(EnsureFpmPool::class)->ensure($component->server, $component->user, $component->environment->runtime_version ?? '');
        }
        app(\App\Actions\Component\SyncComponentVhost::class)->sync($component);
        if ($mode !== WebMode::PROXY) {
            return;
        }

        $worker = $component->workers()->where('component_capability_id', $capability->id)->first();
        if (! $worker) {
            app(CreateWorker::class)->create($component->server, [
                'name' => 'app', 'command' => $capability->config['start_command'],
                'user' => $component->user, 'auto_start' => true, 'auto_restart' => true, 'numprocs' => 1,
            ], component: $component, managedBy: $capability);

            return;
        }

        $worker->command = $capability->config['start_command'];
        $worker->save();
        app(InstallWorker::class)->install($worker, replace: true);
    }

    public function uninstall(ComponentCapability $capability, Component $component): void
    {
        app(\App\Actions\Component\SyncLoadBalancerBackends::class)->remove($component);
        app(\App\Actions\Component\SyncComponentVhost::class)->sync($component);
        foreach ($component->workers()->where('component_capability_id', $capability->id)->get() as $worker) {
            app(DeleteWorker::class)->remove($worker);
        }
        if ($component->environment->application->preset()->webMode() === WebMode::FPM) {
            foreach (array_unique(array_filter([$component->installed_runtime_version, $component->environment->runtime_version])) as $version) {
                $this->removePool($component, $version);
            }
        }
    }

    public function removePool(Component $component, string $version): void
    {
        $user = $component->isolatedUser;
        if (! $user || $component->user === $component->server->getSshUser()) {
            return;
        }
        $user->lock()->block(30, function () use ($component, $user, $version): void {
            if ($user->fpmPoolUsedByOthers($version, $component)) {
                return;
            }
            $service = $component->server->services()->where('type', 'php')->where('version', $version)->first();
            if ($service) {
                /** @var PHP $php */
                $php = $service->handler();
                $php->removeFpmPool($component->user, $version, null);
            }
        });
    }
}
