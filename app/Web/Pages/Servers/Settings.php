<?php

namespace App\Web\Pages\Servers;

use App\Actions\Server\RebootServer;
use App\Models\Server;
use App\Web\Pages\Servers\Widgets\ServerDetails;
use App\Web\Pages\Servers\Widgets\UpdateServerInfo;
use App\Web\Traits\PageHasServer;
use App\Web\Traits\PageHasWidgets;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page
{
    use PageHasServer;
    use PageHasWidgets;

    protected static ?string $slug = 'servers/{server}/settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Settings';

    protected $listeners = ['$refresh'];

    public Server $server;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', static::getServerFromRoute()) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [
                ServerDetails::class, ['server' => $this->server],
            ],
            [
                UpdateServerInfo::class, ['server' => $this->server],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->record($this->server)
                ->modalHeading('Delete Server')
                ->modalDescription('Once your server is deleted, all of its resources and data will be permanently deleted and can\'t be restored'),
            Action::make('reboot')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->label('Reboot')
                ->requiresConfirmation()
                ->action(function () {
                    app(RebootServer::class)->reboot($this->server);

                    $this->dispatch('$refresh');

                    Notification::make()
                        ->info()
                        ->title('Server is being rebooted')
                        ->send();
                }),
        ];
    }

    protected function getServer(): ?Server
    {
        return $this->server;
    }
}
