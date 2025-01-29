<?php

namespace App\Web\Pages\Servers\Sites\Pages\Logs;

use App\Models\ServerLog;
use App\Web\Pages\Servers\Logs\Widgets\LogsList;
use App\Web\Pages\Servers\Sites\Page;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/logs';

    protected static ?string $title = 'Logs';

    public function mount(): void
    {
        $this->authorize('viewAny', [ServerLog::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [
                LogsList::class, [
                    'server' => $this->server,
                    'site' => $this->site,
                    'label' => 'Logs',
                ],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
