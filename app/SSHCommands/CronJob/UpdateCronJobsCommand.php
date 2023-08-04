<?php

namespace App\SSHCommands\CronJob;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class UpdateCronJobsCommand extends Command
{
    public function __construct(protected string $user, protected string $data)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/cronjobs/update-cron-jobs.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__user__', $this->user)
            ->replace('__data__', $this->data)
            ->toString();
    }
}
