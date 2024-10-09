<?php

namespace App\Web\Pages\Servers\Logs;

use App\Models\ServerLog;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Logs\Widgets\LogsList;
use App\Web\Pages\Servers\Page;

class Index extends Page implements HasSecondSubNav
{
    use Traits\Navigation;

    protected static ?string $slug = 'servers/{server}/logs';

    protected static ?string $title = 'Logs';

    public function mount(): void
    {
        $this->authorize('viewAny', [ServerLog::class, $this->server]);
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
