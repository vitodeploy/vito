<?php

namespace App\Web\Pages\Servers\Databases;

use App\Models\Backup;
use App\Models\Server;
use App\Web\Components\Page;
use App\Web\Traits\PageHasServer;

class Backups extends Page
{
    use PageHasServer;
    use Traits\Navigation;

    protected static ?string $slug = 'servers/{server}/databases/backups';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Backups';

    public Server $server;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', [Backup::class, static::getServerFromRoute()]) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
