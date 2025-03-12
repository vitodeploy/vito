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
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return [
            Action::make('deploy')
                ->label('Deploy a Key')
                ->authorize(fn () => $user->can('createServer', [SshKey::class, $this->server]))
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
                        ->options($user->sshKeys()->pluck('name', 'id')->toArray())
                        ->visible(fn ($get): bool => $get('type') === 'existing')
                        ->rules(DeployKeyToServer::rules($user, $this->server)['key_id']),
                    TextInput::make('name')
                        ->label('Name')
                        ->visible(fn ($get): bool => $get('type') === 'new')
                        ->rules(CreateSshKey::rules()['name']),
                    Textarea::make('public_key')
                        ->label('Public Key')
                        ->visible(fn ($get): bool => $get('type') === 'new')
                        ->rules(CreateSshKey::rules()['public_key']),
                ])
                ->modalSubmitActionLabel('Deploy')
                ->action(function (array $data) use ($user): void {
                    $this->validate();

                    try {
                        if (! isset($data['key_id'])) {
                            $data['key_id'] = app(CreateSshKey::class)->create($user, $data)->id;
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
