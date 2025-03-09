<?php

namespace App\Web\Pages\Servers\Databases;

use App\Actions\Database\CreateDatabase;
use App\Models\Database;
use App\Models\Server;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
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

    public static function getCharsetInput(Server $server): Select
    {
        return Select::make('charset')
            ->label('Charset / Encoding')
            ->native(false)
            ->live()
            ->default(function () use ($server) {
                $service = $server->defaultService('database');

                return $service->type_data['defaultCharset'] ?? null;
            })
            ->options(function () use ($server): array {
                $service = $server->defaultService('database');
                $charsets = $service->type_data['charsets'] ?? [];

                return array_combine(
                    array_keys($charsets),
                    array_keys($charsets)
                );
            })
            ->afterStateUpdated(function (Get $get, Set $set, $state) use ($server): void {
                $service = $server->defaultService('database');
                $charsets = $service->type_data['charsets'] ?? [];
                $set('collation', $charsets[$state]['default'] ?? null);
            })
            ->rules(fn (callable $get) => CreateDatabase::rules($server, $get())['charset']);
    }

    public static function getCollationInput(Server $server): Select
    {
        return Select::make('collation')
            ->label('Collation')
            ->native(false)
            ->live()
            ->default(function (Get $get) use ($server) {
                $service = $server->defaultService('database');
                $charsets = $service->type_data['charsets'] ?? [];
                $charset = $get('charset') ?? $service->type_data['default'] ?? 'utf8mb4';

                return $charsets[$charset]['default'] ?? null;
            })
            ->options(function (Get $get) use ($server): array {
                $service = $server->defaultService('database');
                $collations = $service->type_data['charsets'][$get('charset')]['list'] ?? [];

                return array_combine(
                    array_values($collations),
                    array_values($collations)
                );
            })
            ->rules(fn (callable $get) => CreateDatabase::rules($server, $get())['collation']);
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
                    self::getCharsetInput($this->server),
                    self::getCollationInput($this->server),
                    Checkbox::make('user')
                        ->label('Create User')
                        ->default(false)
                        ->reactive(),
                    TextInput::make('username')
                        ->label('Username')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['username'])
                        ->hidden(fn (callable $get): bool => $get('user') !== true),
                    TextInput::make('password')
                        ->label('Password')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['password'])
                        ->hidden(fn (callable $get): bool => $get('user') !== true),
                    Checkbox::make('remote')
                        ->label('Remote')
                        ->default(false)
                        ->hidden(fn (callable $get): bool => $get('user') !== true)
                        ->reactive(),
                    TextInput::make('host')
                        ->label('Host')
                        ->rules(fn (callable $get) => CreateDatabase::rules($this->server, $get())['host'])
                        ->hidden(fn (callable $get): bool => $get('remote') !== true),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data): void {
                    run_action($this, function () use ($data): void {
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
