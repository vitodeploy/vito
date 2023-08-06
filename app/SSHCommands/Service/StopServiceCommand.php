<?php

namespace App\SSHCommands\Service;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class StopServiceCommand extends Command
{
    public function __construct(protected string $unit)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/service/stop-service.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__service__', $this->unit)
            ->toString();
    }
}
