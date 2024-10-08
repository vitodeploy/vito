<?php

namespace App\Web\Pages\Servers\Databases;

use App\Actions\Database\CreateDatabase;
use App\Models\Database;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page implements HasSecondSubNav
{
    use Traits\Navigation;

    protected static ?string $slug = 'servers/{server}/databases';

    protected static ?string $title = 'Databases';

    public function mount(): void
    {
        $this->authorize('viewAny', [Database::class, $this->server]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Database')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::Large)
                ->authorize(fn () => auth()->user()?->can('create', [Database::class, $this->server]))
                ->form([
                    TextInput::make('name')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['name']),
                    Checkbox::make('user')
                        ->label('Create User')
                        ->default(false)
                        ->reactive(),
                    TextInput::make('username')
                        ->label('Username')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['username'])
                        ->hidden(fn (callable $get) => $get('user') !== true),
                    TextInput::make('password')
                        ->label('Password')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['password'])
                        ->hidden(fn (callable $get) => $get('user') !== true),
                    Checkbox::make('remote')
                        ->label('Remote')
                        ->default(false)
                        ->hidden(fn (callable $get) => $get('user') !== true)
                        ->reactive(),
                    TextInput::make('host')
                        ->label('Host')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['host'])
                        ->hidden(fn (callable $get) => $get('remote') !== true),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(CreateDatabase::class)->create($this->server, $data);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Database Created!')
                            ->send();
                    });
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\DatabasesList::class, ['server' => $this->server]],
        ];
    }
}
