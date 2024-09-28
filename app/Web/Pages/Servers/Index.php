<?php

namespace App\Web\Pages\Servers;

use App\Models\Server;
use App\Web\Components\Page;
use Filament\Actions\Action;

class Index extends Page
{
    protected static ?string $slug = 'servers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Servers';

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName().'*';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Server::class) ?? false;
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
                ->icon('heroicon-o-plus')
                ->url(Create::getUrl())
                ->authorize('create', Server::class),
        ];
    }
}
