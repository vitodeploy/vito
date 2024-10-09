<?php

namespace App\Web\Pages\Servers\Logs;

use App\Actions\Server\CreateServerLog;
use App\Models\ServerLog;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Logs\Widgets\LogsList;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;

class RemoteLogs extends Page implements HasSecondSubNav
{
    use Traits\Navigation;

    protected static ?string $slug = 'servers/{server}/logs/remote';

    protected static ?string $title = 'Remote Logs';

    public function mount(): void
    {
        $this->authorize('viewAny', [ServerLog::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [LogsList::class, ['server' => $this->server, 'remote' => true]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::Large)
                ->authorize(fn () => auth()->user()?->can('create', [ServerLog::class, $this->server]))
                ->form([
                    TextInput::make('path')
                        ->helperText('The full path of the log file on the server')
                        ->rules(fn (callable $get) => CreateServerLog::rules()['path']),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data) {
                    app(CreateServerLog::class)->create($this->server, $data);

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
