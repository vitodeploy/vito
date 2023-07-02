<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateBranchCommand extends Command
{
    protected $path;

    protected $branch;

    public function __construct($path, $branch)
    {
        $this->path = $path;
        $this->branch = $branch;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/update-branch.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__path__', $this->path, $this->file($os));

        return Str::replace('__branch__', $this->branch, $command);
    }
}
