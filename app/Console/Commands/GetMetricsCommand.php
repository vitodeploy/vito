<?php

namespace App\Console\Commands;

use App\Enums\ServerStatus;
use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class GetMetricsCommand extends Command
{
    protected $signature = 'metrics:get';

    protected $description = 'Get server metrics';

    public function handle(): void
    {
        $checkedMetrics = 0;
        Server::query()
            ->where('status', ServerStatus::READY)
            ->whereHas('services', function (Builder $query): void {
                $query->where('type', 'monitoring')
                    ->where('name', 'remote-monitor');
            })->chunk(10, function ($servers) use (&$checkedMetrics): void {
                /** @var Server $server */
                foreach ($servers as $server) {
                    $info = $server->os()->resourceInfo();
                    $server->metrics()->create(array_merge($info, ['server_id' => $server->id]));
                    $checkedMetrics++;
                }
            });
        $this->info("Checked $checkedMetrics metrics");
    }
}
