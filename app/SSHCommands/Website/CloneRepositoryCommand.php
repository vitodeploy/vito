<?php

namespace App\SSHCommands\Website;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CloneRepositoryCommand extends Command
{
    public function __construct(
        protected string $repository,
        protected string $path,
        protected string $branch,
        protected string $privateKeyPath
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/website/clone-repository.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__repo__', $this->repository)
            ->replace('__host__', str($this->repository)->after('@')->before('-'))
            ->replace('__branch__', $this->branch)
            ->replace('__path__', $this->path)
            ->replace('__key__', $this->privateKeyPath)
            ->toString();
    }
}
