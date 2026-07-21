<?php

namespace App\Console\Commands;

use App\Actions\Service\CheckServiceStatuses;
use App\Enums\ServerStatus;
use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

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
                    try {
                        $info = $server->os()->resourceInfo();
                        $server->metrics()->create(array_merge($info, ['server_id' => $server->id]));
                        $checkedMetrics++;
                    } catch (Throwable $e) {
                        Log::warning('Failed to collect metrics for server', [
                            'server_id' => $server->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    try {
                        app(CheckServiceStatuses::class)->check($server);
                    } catch (Throwable $e) {
                        Log::warning('Failed to check service statuses for server', [
                            'server_id' => $server->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        $this->info("Checked $checkedMetrics metrics");
    }
}
