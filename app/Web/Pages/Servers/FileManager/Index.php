<?php

namespace App\Web\Pages\Servers\FileManager;

use App\Web\Pages\Servers\Page;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/file-manager';

    protected static ?string $title = 'File Manager';

    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        $this->authorize('manage', $this->server);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\FilesList::class, ['server' => $this->server]],
        ];
    }
}
