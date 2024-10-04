<?php

namespace App\Web\Pages\Servers\Sites\Pages\Queues;

use App\Web\Pages\Servers\Sites\Page;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/queues';

    protected static ?string $title = 'Queues';

    public static function canAccess(): bool
    {
        return true;
    }

    public function getWidgets(): array
    {
        return [];
    }
}
