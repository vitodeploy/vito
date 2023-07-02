<?php

namespace App\Jobs\CronJob;

use App\Enums\CronjobStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\CronJob;
use App\SSHCommands\UpdateCronJobsCommand;
use Throwable;

class AddToServer extends Job
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
        $this->cronJob->status = CronjobStatus::READY;
        $this->cronJob->save();
        event(
            new Broadcast('add-cronjob-finished', [
                'cronJob' => $this->cronJob,
            ])
        );
    }

    public function failed(): void
    {
        $this->cronJob->delete();
        event(
            new Broadcast('add-cronjob-failed', [
                'cronJob' => $this->cronJob,
            ])
        );
    }
}
