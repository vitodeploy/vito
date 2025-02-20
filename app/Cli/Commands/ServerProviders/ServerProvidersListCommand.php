<?php

namespace App\Cli\Commands\ServerProviders;

use App\Cli\Commands\AbstractCommand;
use App\Models\Project;

use App\Models\Server;
use App\Models\ServerProvider;
use function Laravel\Prompts\table;

class ServerProvidersListCommand extends AbstractCommand
{
    protected $signature = 'server-providers:list';

    protected $description = 'Show server providers list';

    public function handle(): void
    {
        $providers = $this->user()->serverProviders;

        table(
            headers: ['ID', 'Provider', 'Name', 'Created At'],
            rows: $providers->map(fn (ServerProvider $provider) => [
                $provider->id,
                $provider->provider,
                $provider->profile,
                $provider->created_at_by_timezone,
            ])->toArray(),
        );
    }
}
