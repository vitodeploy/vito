<?php

namespace App\Web\Pages\Servers\Sites\Pages\SSL;

use App\Web\Pages\Servers\Sites\Page;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/ssl';

    protected static ?string $title = 'SSL';

    public static function canAccess(): bool
    {
        return true;
    }

    public function getWidgets(): array
    {
        return [];
    }
}
