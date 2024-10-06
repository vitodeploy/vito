<?php

namespace App\Web\Pages\Servers\Logs;

use App\Models\ServerLog;
use App\Web\Pages\Servers\Logs\Widgets\LogsList;
use App\Web\Pages\Servers\Page;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/logs';

    protected static ?string $title = 'Logs';

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
