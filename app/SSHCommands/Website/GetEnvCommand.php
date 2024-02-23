<?php

namespace App\SSHCommands\Website;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class GetEnvCommand extends Command
{
    public function __construct(
        protected string $domain
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/website/get-env.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__domain__', $this->domain)
            ->toString();
    }
}
