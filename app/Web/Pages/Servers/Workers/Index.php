<?php

namespace App\Web\Pages\Servers\Workers;

use App\Models\Worker;
use App\Web\Pages\Servers\Page;
use App\Web\Pages\Servers\Workers\Actions\Create;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/workers';

    protected static ?string $title = 'Workers';

    public function mount(): void
    {
        $this->authorize('viewAny', [Worker::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\WorkersList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/workers')
                ->openUrlInNewTab(),
            CreateAction::make('create')
                ->icon('heroicon-o-plus')
                ->createAnother(false)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->label('New Worker')
                ->form(Create::form($this->server))
                ->using(fn (array $data) => run_action($this, function () use ($data): void {
                    Create::action($this, $data, $this->server);
                })),
        ];
    }
}
