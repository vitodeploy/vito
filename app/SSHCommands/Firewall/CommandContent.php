<?php

namespace App\SSHCommands\Firewall;

use Illuminate\Support\Str;

trait CommandContent
{
    public function content(string $os): string
    {
        $command = Str::replace('__type__', $this->type, $this->file($os));
        $command = Str::replace('__protocol__', $this->protocol, $command);
        $command = Str::replace('__source__', $this->source, $command);
        $command = Str::replace('__mask__', $this->mask, $command);

        return Str::replace('__port__', $this->port, $command);
    }
}
