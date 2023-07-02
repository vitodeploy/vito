<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CloneRepositoryCommand extends Command
{
    /**
     * @var string
     */
    protected $repository;

    /**
     * @var string
     */
    protected $path;

    protected $branch;

    /**
     * CloneRepositoryCommand constructor.
     */
    public function __construct($repository, $path, $branch)
    {
        $this->repository = $repository;
        $this->path = $path;
        $this->branch = $branch;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/clone-repository.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__repo__', $this->repository, $this->file($os));
        $command = Str::replace('__host__', get_hostname_from_repo($this->repository), $command);
        $command = Str::replace('__branch__', $this->branch, $command);

        return Str::replace('__path__', $this->path, $command);
    }
}
