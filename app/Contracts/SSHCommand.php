<?php

namespace App\Contracts;

interface SSHCommand
{
    public function file(string $os): string;

    public function content(string $os): string;
}
