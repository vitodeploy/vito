<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateCronJobsCommand extends Command
{
    protected $user;

    protected $data;

    /**
     * UpdateCronJobsCommand constructor.
     */
    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/update-cron-jobs.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__user__', $this->user, $this->file($os));

        return Str::replace('__data__', $this->data, $command);
    }
}
