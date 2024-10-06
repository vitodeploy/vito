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

        if ($this->server->webserver()) {
            $stats[] = Stat::make('Sites', $this->server->sites()->count())
                ->icon('heroicon-o-cursor-arrow-ripple');
        }

        if ($this->server->database()) {
            $stats[] = Stat::make('Databases', $this->server->databases()->count())
                ->icon('heroicon-o-circle-stack');
        }

        if ($this->server->defaultService('php')) {
            $stats[] = Stat::make('PHP Version', $this->server->defaultService('php')->version)
                ->icon('heroicon-o-command-line');
        }

        return $stats;
    }
}
