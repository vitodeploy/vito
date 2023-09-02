<?php

namespace App\SSHCommands\SSL;

use App\SSHCommands\Command;

class RemoveSSLCommand extends Command
{
    public function __construct(protected string $path)
    {
    }

    public function file(): string
    {
        return '';
    }

    public function content(): string
    {
        return 'sudo rm -rf '.$this->path.'*';
    }
}
