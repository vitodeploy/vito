<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class DeleteOlderMetricsCommand extends Command
{
    protected $signature = 'metrics:delete-older-metrics';

    protected $description = 'Delete older metrics from database';

    public function handle(): void
    {
        Service::query()->where('type', 'monitoring')->chunk(100, function ($services): void {
            $services->each(function ($service): void {
                $this->info("Deleting older metrics for service {$service->server->name}");
                $service
                    ->server
                    ->metrics()
                    ->where('created_at', '<', now()->subDays($service->handler()->data()['data_retention']))
                    ->delete();
                $this->info('Metrics deleted');
            });
        });
    }
}
