<?php

namespace App\SSHCommands\ProcessManager\Supervisor;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateWorkerCommand extends Command
{
    protected $id;

    protected $config;

    public function __construct($id, $config)
    {
        $this->id = $id;
        $this->config = $config;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/process-manager/supervisor/create-worker.sh'));
    }

    public function content(string $os): string
    {
        $command = $this->file($os);
        $command = Str::replace('__id__', $this->id, $command);

        return Str::replace('__config__', $this->config, $command);
    }
}
