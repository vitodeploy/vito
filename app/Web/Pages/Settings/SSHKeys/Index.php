<?php

namespace App\Web\Pages\Settings\SSHKeys;

use App\Actions\SshKey\CreateSshKey;
use App\Models\SshKey;
use App\Models\User;
use App\Web\Components\Page;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/ssh-keys';

    protected static ?string $title = 'SSH Keys';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 9;

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('viewAny', SshKey::class);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\SshKeysList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            CreateAction::make('add')
                ->label('Add Key')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add a new Key')
                ->modalSubmitActionLabel('Add')
                ->createAnother(false)
                ->form([
                    TextInput::make('name')
                        ->label('Name')
                        ->rules(CreateSshKey::rules()['name']),
                    Textarea::make('public_key')
                        ->label('Public Key')
                        ->rules(CreateSshKey::rules()['public_key']),
                ])
                ->authorize('create', SshKey::class)
                ->modalWidth(MaxWidth::Large)
                ->using(function (array $data) use ($user): void {
                    app(CreateSshKey::class)->create($user, $data);

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
