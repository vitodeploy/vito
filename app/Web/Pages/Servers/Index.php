<?php

namespace App\Web\Pages\Servers;

use App\Models\Server;
use App\Web\Traits\PageHasWidgets;
use Filament\Actions\Action;
use Filament\Pages\Page;

class Index extends Page
{
    use PageHasWidgets;

    protected static ?string $slug = 'servers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Servers';

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName().'*';
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ServersList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create a Server')
                ->url(Create::getUrl())
                ->authorize('create', Server::class),
        ];
    }
}
