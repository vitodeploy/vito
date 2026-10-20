<?php

namespace App\Actions\CronJob;

use App\Actions\Component\ValidateComponentProcess;
use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\Component;
use App\Models\CronJob;
use App\Models\Server;

class DeleteCronJob
{
    /**
     * @throws SSHError
     */
    public function delete(Server $server, CronJob $cronJob, ?Component $component = null): void
    {
        $component ??= $cronJob->component;
        app(SyncCronJobs::class)->run($server, fn () => app(ValidateComponentProcess::class)->run($component, function () use ($server, $cronJob, $component): void {
            $cronJob->refresh();
            app(ValidateComponentProcess::class)->validate($server, [], $component, 'scheduler', $cronJob);
            $this->remove($server, $cronJob, locked: true);
        }));
    }

    public function remove(Server $server, CronJob $cronJob, bool $locked = false): void
    {
        if (! $locked) {
            app(SyncCronJobs::class)->run($server, fn () => $this->remove($server, $cronJob, locked: true), background: true);

            return;
        }
        app(SyncCronJobs::class)->sync($server, locked: true);

        $user = $cronJob->user;
        $cronJob->status = CronjobStatus::DELETING;
        $cronJob->save();
        $server->cron()->update($cronJob->user, CronJob::crontab($server, $user));
        $cronJob->delete();
    }
}
