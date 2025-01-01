<?php

namespace App\Web\Pages\Servers\Console;

use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/console.html')
                ->openUrlInNewTab(),
        ];
    }
}
