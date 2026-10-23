<?php

namespace App\Actions\CronJob;

use App\Actions\Component\ValidateComponentProcess;
use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;

class DisableCronJob
{
    /**
     * @throws SSHError
     */
    public function disable(Server $server, CronJob $cronJob): void
    {
        app(SyncCronJobs::class)->run($server, fn () => app(ValidateComponentProcess::class)->run($cronJob->component, function () use ($server, $cronJob): void {
            $cronJob->refresh();
            app(ValidateComponentProcess::class)->validate($server, [], $cronJob->component, 'scheduler', $cronJob);
            app(SyncCronJobs::class)->sync($server, locked: true);

            $cronJob->status = CronjobStatus::DISABLING;
            $cronJob->save();

            $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
            $cronJob->status = CronjobStatus::DISABLED;
            $cronJob->save();
        }), atomic: $cronJob->component_id !== null);
    }
}
