<?php

namespace App\Web\Pages\Servers\Console;

use App\Web\Pages\Servers\Page;

class Index extends Page
{
    protected ?string $live = '';

    protected $listeners = [];

    protected static ?string $slug = 'servers/{server}/console';

    protected static ?string $title = 'Headless Console';

    protected static ?string $navigationLabel = 'Console';

    public function mount(): void
    {
        $this->authorize('manage', $this->server);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\Console::class, ['server' => $this->server]],
        ];
    }
}
