<?php

namespace App\Jobs\CronJob;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\CronJob;
use App\SSHCommands\CronJob\UpdateCronJobsCommand;
use Throwable;

class RemoveFromServer extends Job
{
    protected CronJob $cronJob;

    public function __construct(CronJob $cronJob)
    {
        $this->cronJob = $cronJob;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->cronJob->server->ssh()->exec(
            new UpdateCronJobsCommand($this->cronJob->user, $this->cronJob->crontab),
            'update-crontab'
        );
        $this->cronJob->delete();
        event(
            new Broadcast('remove-cronjob-finished', [
                'id' => $this->cronJob->id,
            ])
        );
    }

    public function failed(): void
    {
        $this->cronJob->save();
        event(
            new Broadcast('remove-cronjob-failed', [
                'cronJob' => $this->cronJob,
            ])
        );
    }
}
