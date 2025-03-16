<?php

namespace App\Web\Pages\Servers\Sites\Pages\Workers;

use App\Models\Worker;
use App\Web\Pages\Servers\Sites\Page;
use App\Web\Pages\Servers\Workers\Actions\Create;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/workers';

    protected static ?string $title = 'Workers';

    public function mount(): void
    {
        $this->authorize('viewAny', [Worker::class, $this->server, $this->site]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\WorkersList::class, [
                'server' => $this->server,
                'site' => $this->site,
            ]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/workers')
                ->openUrlInNewTab(),
            CreateAction::make('create')
                ->icon('heroicon-o-plus')
                ->createAnother(false)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->label('New Worker')
                ->form(Create::form($this->server, $this->site))
                ->using(fn (array $data) => run_action($this, function () use ($data): void {
                    Create::action($this, $data, $this->server, $this->site);
                })),
        ];
    }
}
