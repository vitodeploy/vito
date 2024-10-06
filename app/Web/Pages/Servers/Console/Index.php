<?php

namespace App\Web\Pages\Servers\Console;

use App\Web\Pages\Servers\Page;

class Index extends Page
{
    protected ?string $live = '';

    protected $listeners = [];

    protected static ?string $slug = 'servers/{server}/console';

    protected static ?string $title = 'Console';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', static::getServerFromRoute()) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\Console::class, ['server' => $this->server]],
        ];
    }
}
