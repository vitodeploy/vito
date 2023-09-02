<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateUserCommand extends Command
{
    public function __construct(
        protected string $provider,
        protected string $username,
        protected string $password,
        protected string $host
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path(sprintf('commands/database/%s/create-user.sh', $this->provider)));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__username__', $this->username)
            ->replace('__password__', $this->password)
            ->replace('__host__', $this->host)
            ->toString();
    }
}
