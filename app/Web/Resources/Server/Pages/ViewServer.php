<?php

namespace App\Web\Resources\Server\Pages;

use App\Actions\Server\RebootServer;
use App\Models\Server;
use App\Web\Resources\Server\ServerResource;
use App\Web\Resources\Server\Widgets\InstallingServer;
use App\Web\Resources\Server\Widgets\ServerDetails;
use App\Web\Resources\Server\Widgets\UpdateServerInfo;
use App\Web\Traits\HasServerInfoWidget;
use App\Web\Traits\PageHasWidgets;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewServer extends Page
{
    use HasServerInfoWidget;
    use PageHasWidgets;

    protected static string $resource = ServerResource::class;

    public Server $server;

    public function getWidgets(): array
    {
        $widgets = [];
        if ($this->server->isInstalling()) {
            $widgets[] = [
                InstallingServer::class, ['server' => $this->server],
            ];
        } else {
            $widgets = array_merge($widgets, [
                [
                    ServerDetails::class, ['server' => $this->server],
                ],
                [
                    UpdateServerInfo::class, ['server' => $this->server],
                ],
            ]);
        }

        return $widgets;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->server->name;
    }

    public function mount(Server $record): void
    {
        $this->server = $record;
    }

    public function refresh(): void
    {
        $this->dispatch('refresh');
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
