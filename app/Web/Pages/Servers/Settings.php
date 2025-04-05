<?php

namespace App\Web\Pages\Servers;

use App\Actions\Server\RebootServer;
use App\Models\Server;
use App\Models\User;
use App\Web\Pages\Servers\Widgets\ServerDetails;
use App\Web\Pages\Servers\Widgets\UpdateServerInfo;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

class Settings extends Page
{
    protected static ?string $slug = 'servers/{server}/settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Settings';

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('update', [$this->server, $user->currentProject]);
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
            Action::make('delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Server')
                ->modalDescription('Once your server is deleted, all of its resources and data will be permanently deleted and can\'t be restored')
                ->authorize('delete', $this->server)
                ->action(function (): void {
                    try {
                        $this->server->delete();

                        Notification::make()
                            ->success()
                            ->title('Server deleted')
                            ->send();

                        $this->redirect(Index::getUrl());
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();
                    }
                }),
            Action::make('reboot')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->label('Reboot')
                ->requiresConfirmation()
                ->action(function (): void {
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
