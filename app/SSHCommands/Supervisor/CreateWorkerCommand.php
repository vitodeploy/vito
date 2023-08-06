<?php

namespace App\SSHCommands\Supervisor;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateWorkerCommand extends Command
{
    public function __construct(protected string $id, protected string $config)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/supervisor/create-worker.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__id__', $this->id)
            ->replace('__config__', $this->config)
            ->toString();
    }
}
