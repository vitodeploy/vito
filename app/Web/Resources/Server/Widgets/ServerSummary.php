<?php

namespace App\Web\Resources\Server\Widgets;

use App\Models\Server;
use App\Web\Resources\Server\Pages\ViewServer;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Widget;

class ServerSummary extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected $listeners = ['$refresh'];

    public Server $server;

    protected static bool $isLazy = false;

    protected static string $view = 'web.components.infolist';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Fieldset::make('info')
                    ->label('Server Summary')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->url(fn (Server $record) => ViewServer::getUrl(['record' => $record])),
                        TextEntry::make('ip')
                            ->label('IP Address')
                            ->icon('heroicon-o-clipboard-document')
                            ->iconPosition(IconPosition::After)
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(static function ($state): string {
                                return Server::$statusColors[$state];
                            })
                            ->suffixAction(
                                Action::make('check-status')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Check Connection')
                                    ->action(function (Server $record) {
                                        $record = $record->checkConnection();

                                        $this->dispatch('$refresh');

                                        Notification::make()
                                            ->color(Server::$statusColors[$record->status])
                                            ->title('Server is '.$record->status)
                                            ->send();
                                    })
                            ),
                    ])
                    ->columns(3),
            ])
            ->record($this->server->refresh());
    }
}
