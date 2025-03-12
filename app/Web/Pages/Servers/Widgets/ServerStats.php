<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerStats extends BaseWidget
{
    public Server $server;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $stats = [];

        if ($this->server->webserver() instanceof \App\Models\Service) {
            $stats[] = Stat::make('Sites', $this->server->sites()->count())
                ->icon('heroicon-o-cursor-arrow-ripple');
        }

        if ($this->server->database() instanceof \App\Models\Service) {
            $stats[] = Stat::make('Databases', $this->server->databases()->count())
                ->icon('heroicon-o-circle-stack');
        }

        if ($this->server->defaultService('php') instanceof \App\Models\Service) {
            $stats[] = Stat::make('PHP Version', $this->server->defaultService('php')->version)
                ->icon('heroicon-o-command-line');
        }

        return $stats;
    }
}
