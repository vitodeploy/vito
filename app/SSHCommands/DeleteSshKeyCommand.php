<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteSshKeyCommand extends Command
{
    /**
     * @var string
     */
    protected $key;

    /**
     * InstallPHPCommand constructor.
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/delete-ssh-key.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__key__', Str::replace('/', '\/', $this->key), $this->file($os));
    }
}
