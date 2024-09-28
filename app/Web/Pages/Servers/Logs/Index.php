<?php

namespace App\Web\Pages\Servers\Logs;

use App\Models\Server;
use App\Models\ServerLog;
use App\Web\Pages\Servers\Logs\Widgets\LogsList;
use App\Web\Traits\PageHasServer;
use App\Web\Traits\PageHasWidgets;
use Filament\Pages\Page;

class Index extends Page
{
    use PageHasServer;
    use PageHasWidgets;

    protected static ?string $slug = 'servers/{server}/logs';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Logs';

    public Server $server;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', [ServerLog::class, static::getServerFromRoute()]) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [LogsList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
