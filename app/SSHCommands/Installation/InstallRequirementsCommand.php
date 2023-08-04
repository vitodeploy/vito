<?php

namespace App\SSHCommands\Installation;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallRequirementsCommand extends Command
{
    public function __construct(protected string $email, protected string $name)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/installation/install-requirements.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__email__', $this->email)
            ->replace('__name__', $this->name)
            ->toString();
    }
}
