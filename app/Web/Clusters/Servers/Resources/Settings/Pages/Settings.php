<?php

namespace App\Web\Clusters\Servers\Resources\Settings\Pages;

use App\Actions\Server\RebootServer;
use App\Models\Server;
use App\Web\Clusters\Servers\Resources\Settings\SettingsResource;
use App\Web\Clusters\Servers\Resources\Settings\Widgets\ServerDetails;
use App\Web\Clusters\Servers\Resources\Settings\Widgets\UpdateServerInfo;
use App\Web\Traits\PageHasCluster;
use App\Web\Traits\PageHasServerInfoWidget;
use App\Web\Traits\PageHasWidgets;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class Settings extends Page
{
    use PageHasCluster;
    use PageHasServerInfoWidget;
    use PageHasWidgets;

    protected static string $resource = SettingsResource::class;

    protected $listeners = ['$refresh'];

    public Server $server;

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
