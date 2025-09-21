<?php

namespace App\Actions\CronJob;

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
        // Sync before disabling to preserve any manual cronjobs
        app(SyncCronJobs::class)->sync($server);

        $cronJob->status = CronjobStatus::DISABLING;
        $cronJob->save();

        $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
        $cronJob->status = CronjobStatus::DISABLED;
        $cronJob->save();
    }
}
