<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class GetPublicKeyCommand extends Command
{
    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/get-public-key.sh'));
    }

    public function content(string $os): string
    {
        return $this->file($os);
    }
}
