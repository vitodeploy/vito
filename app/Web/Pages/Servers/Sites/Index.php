<?php

namespace App\Web\Pages\Servers\Sites;

use App\Models\Server;
use App\Models\Site;
use App\Web\Pages\Servers\Sites\Widgets\SitesList;
use App\Web\Traits\PageHasServer;
use App\Web\Traits\PageHasWidgets;
use Filament\Actions\CreateAction;
use Filament\Pages\Page;

class Index extends Page
{
    use PageHasServer;
    use PageHasWidgets;

    protected static ?string $slug = 'servers/{server}/sites';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Sites';

    public Server $server;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', [Site::class, static::getServerFromRoute()]) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [SitesList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn () => auth()->user()?->can('create', [Site::class, $this->server]))
                ->createAnother(false)
                ->label('Create a Site')
                ->icon('heroicon-o-plus'),
        ];
    }
}
