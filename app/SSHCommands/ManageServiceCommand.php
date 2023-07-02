<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ManageServiceCommand extends Command
{
    /**
     * @var string
     */
    protected $unit;

    /**
     * @var string
     */
    protected $action;

    /**
     * ServiceStatusCommand constructor.
     */
    public function __construct($unit, $action)
    {
        $this->unit = $unit;
        $this->action = $action;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/manage-service.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__service__', $this->unit, $this->file($os));

        return Str::replace('__action__', $this->action, $command);
    }
}
