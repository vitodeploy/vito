<?php

namespace App\Actions\ComponentCapability;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
use App\Actions\CronJob\SyncCronJobs;
use App\Enums\CronjobStatus;
use App\Models\Component;
use App\Models\ComponentCapability;

class InstallSchedulerCapability
{
    public function install(ComponentCapability $capability, Component $component): void
    {
        $cronJob = $component->cronJobs()->where('component_capability_id', $capability->id)->first();
        if (! $cronJob) {
            app(CreateCronJob::class)->create($component->server, [
                'name' => 'scheduler', 'user' => $component->user,
                'command' => $capability->config['command'], 'frequency' => '* * * * *',
            ], component: $component, managedBy: $capability);

            return;
        }

        app(SyncCronJobs::class)->run($component->server, function () use ($capability, $component, $cronJob): void {
            app(SyncCronJobs::class)->sync($component->server, locked: true);
            $cronJob->command = $capability->config['command'];
            $cronJob->status = CronjobStatus::UPDATING;
            $cronJob->save();
            $component->server->cron()->update($component->user, app(SyncCronJobs::class)->crontab($component->server, $component->user));
            $cronJob->status = CronjobStatus::READY;
            $cronJob->save();
        }, background: true, atomic: true);
    }

    public function uninstall(ComponentCapability $capability, Component $component): void
    {
        foreach ($component->cronJobs()->where('component_capability_id', $capability->id)->get() as $cronJob) {
            app(DeleteCronJob::class)->remove($component->server, $cronJob);
        }
    }
}
