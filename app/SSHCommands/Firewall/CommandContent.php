<?php

namespace App\SSHCommands\Firewall;

trait CommandContent
{
    public function content(): string
    {
        return str($this->file())
            ->replace('__type__', $this->type)
            ->replace('__protocol__', $this->protocol)
            ->replace('__source__', $this->source)
            ->replace('__mask__', $this->mask || $this->mask == 0 ? '/'.$this->mask : '')
            ->replace('__port__', $this->port)
            ->toString();
    }
}
