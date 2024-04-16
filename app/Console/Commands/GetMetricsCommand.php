<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;

class GetMetricsCommand extends Command
{
    protected $signature = 'metrics:get';

    protected $description = 'Get server metrics';

    public function handle(): void
    {
        Server::query()->chunk(10, function ($servers) {
            /** @var Server $server */
            foreach ($servers as $server) {
                $info = $server->os()->resourceInfo();
                $server->metrics()->create(array_merge($info, ['server_id' => $server->id]));
            }
        });
    }
}
