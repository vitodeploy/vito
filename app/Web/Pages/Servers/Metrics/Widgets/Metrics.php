<?php

namespace App\Web\Pages\Servers\Metrics\Widgets;

use App\Actions\Monitoring\GetMetrics;
use App\Models\Metric;
use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Livewire\Attributes\On;

class Metrics extends BaseWidget
{
    public Server $server;

    public array $filters = [];

    protected static bool $isLazy = false;

    #[On('updateFilters')]
    public function updateFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    protected function getStats(): array
    {
        /** @var Metric $lastMetric */
        $lastMetric = $this->server
            ->metrics()
            ->latest()
            ->first();
        $metrics = app(GetMetrics::class)->filter($this->server, $this->filters);

        return [
            Stat::make('CPU Load', $lastMetric?->load ?? 0)
                ->color('success')
                ->chart($metrics->pluck('load')->toArray()),
            Stat::make('Memory Usage', Number::fileSize($lastMetric?->memory_used_in_bytes || 0, 2))
                ->color('warning')
                ->chart($metrics->pluck('memory_used')->toArray()),
            Stat::make('Disk Usage', Number::fileSize($lastMetric?->disk_used_in_bytes || 0, 2))
                ->color('primary')
                ->chart($metrics->pluck('disk_used')->toArray()),
        ];
    }
}
