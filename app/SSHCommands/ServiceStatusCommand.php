<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ServiceStatusCommand extends Command
{
    /**
     * @var string
     */
    protected $unit;

    /**
     * ServiceStatusCommand constructor.
     */
    public function __construct($unit)
    {
        $this->unit = $unit;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/service-status.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__service__', $this->unit, $this->file($os));
    }
}
