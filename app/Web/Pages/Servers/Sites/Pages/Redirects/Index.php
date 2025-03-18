<?php

namespace App\Web\Pages\Servers\Sites\Pages\Redirects;

use App\Models\Redirect;
use App\Web\Pages\Servers\Sites\Page;
use App\Web\Pages\Servers\Sites\Pages\Redirects\Actions\Create;
use App\Web\Pages\Servers\Sites\Pages\Redirects\Widgets\RedirectsList;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/redirects';

    protected static ?string $title = 'Redirects';

    public function mount(): void
    {
        $this->authorize('view', [Redirect::class, $this->site, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [
                RedirectsList::class, [
                    'server' => $this->server,
                    'site' => $this->site,
                    'label' => 'Redirects',
                ],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create')
                ->icon('heroicon-o-plus')
                ->createAnother(false)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->label('New Redirect')
                ->form(Create::form($this->site))
                ->using(fn (array $data) => run_action($this, function () use ($data): void {
                    Create::action($this, $data, $this->site);
                })),
        ];
    }
}
