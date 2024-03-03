<?php

namespace App\Actions\CronJob;

use App\Models\CronJob;
use App\Models\Server;
use App\SSHCommands\CronJob\UpdateCronJobsCommand;
use Throwable;

class DeleteCronJob
{
    /**
     * @throws Throwable
     */
    public function delete(Server $server, CronJob $cronJob): void
    {
        $user = $cronJob->user;
        $cronJob->delete();
        $server->ssh()->exec(
            new UpdateCronJobsCommand($user, CronJob::crontab($server, $user)),
            'update-crontab'
        );
    }
}
