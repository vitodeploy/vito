<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateCommand extends Command
{
    public function __construct(protected string $provider, protected string $name)
    {
    }

    public function file(): string
    {
        return File::get(resource_path(sprintf("commands/database/%s/create.sh", $this->provider)));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__name__', $this->name)
            ->toString();
    }
}
