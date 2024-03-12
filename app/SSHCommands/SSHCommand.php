<?php

namespace App\SSHCommands;

interface SSHCommand
{
    public function content(): string;
}
