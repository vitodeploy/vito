<?php

namespace App\Contracts;

interface SSHCommand
{
    public function file(): string;

    public function content(): string;
}
