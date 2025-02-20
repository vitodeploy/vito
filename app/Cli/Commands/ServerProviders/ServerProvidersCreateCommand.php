<?php

namespace App\Cli\Commands\ServerProviders;

use App\Cli\Commands\AbstractCommand;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ServerProvidersCreateCommand extends AbstractCommand
{
    protected $signature = 'server-providers:create';

    protected $description = 'Create a new server provider';

    public function handle(): void
    {
        $provider = select(
            label: 'What is the server provider?',
            options: collect(config('core.server_providers'))
                ->filter(fn($provider) => $provider != \App\Enums\ServerProvider::CUSTOM)
                ->mapWithKeys(fn($provider) => [$provider => $provider]),
        );
        $profile = text(
            label: 'What should we call this provider profile?',
            required: true,
        );
    }
}
