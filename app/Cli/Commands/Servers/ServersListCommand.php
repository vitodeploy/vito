<?php

namespace App\Cli\Commands\Servers;

use App\Cli\Commands\AbstractCommand;
use App\Models\Project;

use App\Models\Server;
use function Laravel\Prompts\table;

class ServersListCommand extends AbstractCommand
{
    protected $signature = 'servers:list';

    protected $description = 'Show servers list';

    public function handle(): void
    {
        $servers = $this->user()->currentProject->servers;

        table(
            headers: ['ID', 'Name', 'IP', 'Provider', 'OS', 'Status', 'Created At'],
            rows: $servers->map(fn (Server $server) => [
                $server->id,
                $server->name,
                $server->ip,
                $server->provider,
                $server->os,
                $server->status,
                $server->created_at_by_timezone,
            ])->toArray(),
        );
    }
}
