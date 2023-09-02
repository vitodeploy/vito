<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class LinkCommand extends Command
{
    public function __construct(
        protected string $provider,
        protected string $username,
        protected string $host,
        protected string $database
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path(sprintf('commands/database/%s/link.sh', $this->provider)));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__username__', $this->username)
            ->replace('__host__', $this->host)
            ->replace('__database__', $this->database)
            ->toString();
    }
}
