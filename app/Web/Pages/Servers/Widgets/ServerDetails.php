<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Actions\Server\Update;
use App\Models\Server;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ServerDetails extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    protected static string $view = 'web.components.infolist';

    public Server $server;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->heading('Server Details')
                    ->description('More details about your server')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID')
                            ->inlineLabel()
                            ->hintIcon('heroicon-o-information-circle')
                            ->hintIconTooltip('Server unique identifier to use in the API'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                            ->inlineLabel(),
                        TextEntry::make('last_updated_check')
                            ->label('Last Updated Check')
                            ->inlineLabel()
                            ->state(fn (Server $record) => $record->last_update_check?->ago())
                            ->suffixAction(
                                Action::make('check-update')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Check Now')
                                    ->action(function (Server $record) {
                                        $record->checkForUpdates();

                                        $this->dispatch('$refresh');

                                        Notification::make()
                                            ->info()
                                            ->title('Available updates:')
                                            ->body($record->available_updates)
                                            ->send();
                                    })
                            ),
                        TextEntry::make('available_updates')
                            ->label('Available Updates')
                            ->inlineLabel()
                            ->suffixAction(
                                Action::make('update-server')
                                    ->icon('heroicon-o-check-circle')
                                    ->tooltip('Update Now')
                                    ->requiresConfirmation()
                                    ->action(function (Server $record) {
                                        app(Update::class)->update($record);

                                        $this->dispatch('$refresh');

                                        Notification::make()
                                            ->info()
                                            ->title('Updating the server...')
                                            ->send();
                                    })
                            ),
                        TextEntry::make('provider')
                            ->label('Provider')
                            ->inlineLabel(),
                        TextEntry::make('tags')
                            ->label('Tags')
                            ->inlineLabel()
                            ->state(fn (Server $record) => view('web.components.tags', ['tags' => $record->tags]))
                            ->suffixAction(
                                Action::make('edit-tags')
                                    ->icon('heroicon-o-pencil')
                                    ->tooltip('Edit Tags')
                                    ->action(fn (Server $record) => $this->dispatch('$editTags', $record))
                                    ->tooltip('Edit Tags')
                            ),

                    ]),
            ])
            ->record($this->server);
    }
}
