<?php

namespace App\Web\Pages\Servers\SSHKeys;

use App\Actions\SshKey\CreateSshKey;
use App\Actions\SshKey\DeployKeyToServer;
use App\Models\SshKey;
use App\Web\Pages\Servers\Page;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/ssh-keys';

    protected static ?string $title = 'SSH Keys';

    public function mount(): void
    {
        $this->authorize('viewAny', [SshKey::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\SshKeysList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deploy')
                ->label('Deploy a Key')
                ->authorize(fn () => auth()->user()?->can('createServer', [SshKey::class, $this->server]))
                ->icon('heroicon-o-rocket-launch')
                ->modalWidth(MaxWidth::Large)
                ->form([
                    Select::make('type')
                        ->options([
                            'existing' => 'An existing key',
                            'new' => 'A new key',
                        ])
                        ->reactive()
                        ->default('existing'),
                    Select::make('key_id')
                        ->label('Key')
                        ->options(auth()->user()->sshKeys()->pluck('name', 'id')->toArray())
                        ->visible(fn ($get) => $get('type') === 'existing')
                        ->rules(DeployKeyToServer::rules(auth()->user(), $this->server)['key_id']),
                    TextInput::make('name')
                        ->label('Name')
                        ->visible(fn ($get) => $get('type') === 'new')
                        ->rules(CreateSshKey::rules()['name']),
                    Textarea::make('public_key')
                        ->label('Public Key')
                        ->visible(fn ($get) => $get('type') === 'new')
                        ->rules(CreateSshKey::rules()['public_key']),
                ])
                ->modalSubmitActionLabel('Deploy')
                ->action(function (array $data) {
                    $this->validate();

                    try {
                        if (! isset($data['key_id'])) {
                            $data['key_id'] = app(CreateSshKey::class)->create(auth()->user(), $data)->id;
                        }

                        app(DeployKeyToServer::class)->deploy($this->server, $data);
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();

                        throw $e;
                    }

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
