<?php

namespace App\SSHCommands\Website;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class UpdateBranchCommand extends Command
{
    public function __construct(protected string $path, protected string $branch)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/website/update-branch.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->replace('__branch__', $this->branch)
            ->toString();
    }
}
