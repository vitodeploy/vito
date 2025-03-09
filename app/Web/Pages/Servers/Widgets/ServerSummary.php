<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Models\Server;
use App\Web\Pages\Servers\View;
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

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    protected static string $view = 'components.infolist';

    public Server $server;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->name('server-summary')
            ->schema([
                Fieldset::make('info')
                    ->label('Server Summary')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->url(fn (Server $record): string => View::getUrl(parameters: ['server' => $record])),
                        TextEntry::make('ip')
                            ->label('IP Address')
                            ->icon('heroicon-o-clipboard-document')
                            ->iconPosition(IconPosition::After)
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(static fn ($state): string => Server::$statusColors[$state])
                            ->suffixAction(
                                Action::make('check-status')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Check Connection')
                                    ->action(function (Server $record): void {
                                        $previousStatus = $record->status;

                                        $record = $record->checkConnection();

                                        if ($previousStatus !== $record->status) {
                                            $this->redirect(url()->previous());
                                        }

                                        $this->dispatch('$refresh');

                                        Notification::make()
                                            ->status(Server::$statusColors[$record->status])
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
