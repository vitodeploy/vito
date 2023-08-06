<?php

namespace App\SSHCommands\Supervisor;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class DeleteWorkerCommand extends Command
{
    public function __construct(protected string $id)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/supervisor/delete-worker.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__id__', $this->id)
            ->toString();
    }
}
