<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeploySshKeyCommand extends Command
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
        return File::get(base_path('system/commands/ubuntu/deploy-ssh-key.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__key__', addslashes($this->key), $this->file($os));
    }
}
