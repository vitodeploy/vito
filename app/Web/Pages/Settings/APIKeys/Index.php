<?php

namespace App\Web\Pages\Settings\APIKeys;

use App\Models\PersonalAccessToken;
use App\Web\Components\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/api-keys';

    protected static ?string $title = 'API Keys';

    protected static ?string $navigationIcon = 'icon-plug';

    protected static ?int $navigationSort = 11;

    public string $token = '';

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->can('viewAny', PersonalAccessToken::class);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ApiKeysList::class],
        ];
    }

    public function unmountAction(bool $shouldCancelParentActions = true, bool $shouldCloseModal = true): void
    {
        parent::unmountAction($shouldCancelParentActions, $shouldCloseModal);

        $this->token = '';
    }

    protected function getHeaderActions(): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(config('scribe.static.url'))
                ->openUrlInNewTab(),
            Action::make('create')
                ->label('Create new Key')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create a new Key')
                ->modalSubmitActionLabel('Create')
                ->form(function (): array {
                    if ($this->token !== '' && $this->token !== '0') {
                        return [];
                    }

                    return [
                        TextInput::make('name')
                            ->label('Token Name')
                            ->required(),
                        Radio::make('permission')
                            ->options([
                                'read' => 'Read',
                                'write' => 'Read & Write',
                            ])
                            ->required(),
                    ];
                })
                ->infolist(function (): array {
                    if ($this->token !== '' && $this->token !== '0') {
                        return [
                            TextEntry::make('token')
                                ->state($this->token)
                                ->tooltip('Copy')
                                ->copyable()
                                ->helperText('You can see the token only one!'),
                        ];
                    }

                    return [];
                })
                ->authorize('create', PersonalAccessToken::class)
                ->modalWidth(MaxWidth::Large)
                ->action(function (array $data) use ($user): void {
                    $permissions = ['read'];
                    if ($data['permission'] === 'write') {
                        $permissions[] = 'write';
                    }
                    $token = $user->createToken($data['name'], $permissions);

                    $this->dispatch('$refresh');

                    $this->token = $token->plainTextToken;

                    $this->halt();
                })
                ->modalSubmitAction(function () {
                    if ($this->token !== '' && $this->token !== '0') {
                        return false;
                    }
                })
                ->closeModalByClickingAway(fn (): bool => $this->token === '' || $this->token === '0'),
        ];
    }
}
