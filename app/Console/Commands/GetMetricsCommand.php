<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class GetMetricsCommand extends Command
{
    protected $signature = 'metrics:get';

    protected $description = 'Get server metrics';

    public function handle(): void
    {
        Server::query()->whereHas('services', function (Builder $query) {
            $query->where('type', 'monitoring')
                ->where('name', 'remote-monitor');
        })->chunk(10, function ($servers) {
            /** @var Server $server */
            foreach ($servers as $server) {
                $info = $server->os()->resourceInfo();
                $server->metrics()->create(array_merge($info, ['server_id' => $server->id]));
            }
        });
    }
}
