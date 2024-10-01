<?php

namespace App\Web\Pages\Servers\Metrics;

use App\Models\Metric;
use App\Models\Server;
use App\Web\Components\Page;
use App\Web\Traits\PageHasServer;

class Index extends Page
{
    use PageHasServer;

    protected static ?string $slug = 'servers/{server}/metrics';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Metrics';

    public Server $server;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', [Metric::class, static::getServerFromRoute()]) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\FilterForm::class, ['server' => $this->server]],
            [Widgets\MetricDetails::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
