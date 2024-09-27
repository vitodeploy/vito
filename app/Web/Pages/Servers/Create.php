<?php

namespace App\Web\Pages\Servers;

use App\Models\Server;
use App\Web\Traits\PageHasWidgets;
use Filament\Actions\Action;
use Filament\Pages\Page;

class Create extends Page
{
    use PageHasWidgets;

    protected static ?string $slug = 'servers/create';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Create Server';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Server::class) ?? false;
    }

    protected function getExtraAttributes(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\CreateServer::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/create-server.html')
                ->openUrlInNewTab(),
        ];
    }
}
