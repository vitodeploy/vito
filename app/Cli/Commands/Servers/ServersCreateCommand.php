<?php

namespace App\Cli\Commands\Servers;

use App\Cli\Commands\AbstractCommand;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ServersCreateCommand extends AbstractCommand
{
    protected $signature = 'servers:create';

    protected $description = 'Create a new server';

    public function handle(): void
    {
        $name = text(
            label: 'What is the server name?',
            required: true,
        );
        $os = select(
            label: 'What is the server OS?',
            options: collect(config('core.operating_systems'))
                ->mapWithKeys(fn($value) => [$value => $value]),
        );
    }
}
