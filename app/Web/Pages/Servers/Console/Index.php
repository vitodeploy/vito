<?php

namespace App\Web\Pages\Servers\Console;

use App\Models\Server;
use App\Web\Components\Page;
use App\Web\Traits\PageHasServer;

class Index extends Page
{
    use PageHasServer;

    protected ?string $live = '';

    protected $listeners = [];

    protected static ?string $slug = 'servers/{server}/console';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Console';

    public Server $server;

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
