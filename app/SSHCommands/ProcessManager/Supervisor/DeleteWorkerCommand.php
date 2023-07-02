<?php

namespace App\SSHCommands\ProcessManager\Supervisor;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteWorkerCommand extends Command
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/process-manager/supervisor/delete-worker.sh'));
    }

    public function content(string $os): string
    {
        $command = $this->file($os);

        return Str::replace('__id__', $this->id, $command);
    }
}
