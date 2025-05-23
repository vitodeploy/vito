<?php

namespace App\Web\Pages\Servers\Databases;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\SyncDatabaseUsers;
use App\Models\DatabaseUser;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Users extends Page implements HasSecondSubNav
{
    use Traits\Navigation;

    protected static ?string $slug = 'servers/{server}/databases/users';

    protected static ?string $title = 'Database Users';

    public function mount(): void
    {
        $this->authorize('viewAny', [DatabaseUser::class, $this->server]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->color('gray')
                ->label('Sync Users')
                ->icon('heroicon-o-arrow-path')
                ->authorize(fn () => auth()->user()?->can('create', [DatabaseUser::class, $this->server]))
                ->requiresConfirmation()
                ->modalDescription('This will create db users that exist on the server but not in Vito.')
                ->modalSubmitActionLabel('Sync')
                ->action(function (): void {
                    run_action($this, function (): void {
                        app(SyncDatabaseUsers::class)->sync($this->server);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Users synced!')
                            ->send();
                    });
                }),
            Action::make('create')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::Large)
                ->authorize(fn () => auth()->user()?->can('create', [DatabaseUser::class, $this->server]))
                ->form([
                    TextInput::make('username')
                        ->label('Username')
                        ->rules(fn (callable $get) => CreateDatabaseUser::rules($this->server, $get())['username']),
                    TextInput::make('password')
                        ->label('Password')
                        ->rules(fn (callable $get) => CreateDatabaseUser::rules($this->server, $get())['password']),
                    Checkbox::make('remote')
                        ->label('Remote')
                        ->default(false)
                        ->visible(in_array($this->server->database()->name, ['mysql', 'mariadb']))
                        ->reactive(),
                    TextInput::make('host')
                        ->label('Host')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['host'])
                        ->hidden(fn (callable $get): bool => $get('remote') !== true),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data): void {
                    run_action($this, function () use ($data): void {
                        app(CreateDatabaseUser::class)->create($this->server, $data);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Database user created!')
                            ->send();
                    });
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\DatabaseUsersList::class, ['server' => $this->server]],
        ];
    }
}
