<?php

namespace App\SSHCommands;

class RemoveSSLCommand extends Command
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function file(string $os): string
    {
        return '';
    }

    public function content(string $os): string
    {
        return 'sudo rm -rf '.$this->path.'*'."\n";
    }
}
