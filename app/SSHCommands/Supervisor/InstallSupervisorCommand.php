<?php

namespace App\SSHCommands\Supervisor;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallSupervisorCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/supervisor/install-supervisor.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
