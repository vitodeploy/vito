<?php

namespace App\Web\Pages\Servers;

use App\Actions\Server\CreateServer as CreateServerAction;
use App\Enums\ServerProvider;
use App\Enums\ServerType;
use App\Models\Server;
use App\Web\Components\Page;
use App\Web\Fields\AlertField;
use App\Web\Fields\ProviderField;
use App\Web\Pages\Settings\ServerProviders\Actions\Create;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Servers';

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName().'*';
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Server::class);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ServersList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        $publicKey = __('servers.create.public_key_text', [
            'public_key' => get_public_key_content(),
        ]);

        return [
            \Filament\Actions\Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/create-server.html')
                ->openUrlInNewTab(),
            \Filament\Actions\Action::make('create')
                ->label('Create a Server')
                ->icon('heroicon-o-plus')
                ->authorize('create', Server::class)
                ->modalWidth(MaxWidth::FiveExtraLarge)
                ->slideOver()
                ->form([
                    ProviderField::make('provider')
                        ->label('Select a provider')
                        ->default(ServerProvider::CUSTOM)
                        ->live()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set) {
                            $set('server_provider', null);
                            $set('region', null);
                            $set('plan', null);
                        })
                        ->rules(fn ($get) => CreateServerAction::rules($get())['provider']),
                    AlertField::make('alert')
                        ->warning()
                        ->message(__('servers.create.public_key_warning'))
                        ->visible(fn ($get) => $get('provider') === ServerProvider::CUSTOM),
                    Select::make('server_provider')
                        ->visible(fn ($get) => $get('provider') !== ServerProvider::CUSTOM)
                        ->label('Server provider connection')
                        ->rules(fn ($get) => CreateServerAction::rules($get())['server_provider'])
                        ->options(function ($get) {
                            return \App\Models\ServerProvider::getByProjectId(auth()->user()->current_project_id)
                                ->where('provider', $get('provider'))
                                ->pluck('profile', 'id');
                        })
                        ->suffixAction(
                            Action::make('connect')
                                ->form(Create::form())
                                ->modalHeading('Connect to a new server provider')
                                ->modalSubmitActionLabel('Connect')
                                ->icon('heroicon-o-wifi')
                                ->tooltip('Connect to a new server provider')
                                ->modalWidth(MaxWidth::Medium)
                                ->authorize(fn () => auth()->user()->can('create', \App\Models\ServerProvider::class))
                                ->action(fn (array $data) => Create::action($data))
                        )
                        ->placeholder('Select profile')
                        ->native(false)
                        ->live()
                        ->reactive()
                        ->selectablePlaceholder(false)
                        ->visible(fn ($get) => $get('provider') !== ServerProvider::CUSTOM),
                    Grid::make()
                        ->schema([
                            Select::make('region')
                                ->label('Region')
                                ->rules(fn ($get) => CreateServerAction::rules($get())['region'])
                                ->live()
                                ->reactive()
                                ->options(function ($get) {
                                    if (! $get('server_provider')) {
                                        return [];
                                    }

                                    return \App\Models\ServerProvider::regions($get('server_provider'));
                                })
                                ->loadingMessage('Loading regions...')
                                ->disabled(fn ($get) => ! $get('server_provider'))
                                ->placeholder(fn ($get) => $get('server_provider') ? 'Select region' : 'Select connection first')
                                ->searchable(),
                            Select::make('plan')
                                ->label('Plan')
                                ->rules(fn ($get) => CreateServerAction::rules($get())['plan'])
                                ->reactive()
                                ->options(function ($get) {
                                    if (! $get('server_provider') || ! $get('region')) {
                                        return [];
                                    }

                                    return \App\Models\ServerProvider::plans($get('server_provider'), $get('region'));
                                })
                                ->loadingMessage('Loading plans...')
                                ->disabled(fn ($get) => ! $get('region'))
                                ->placeholder(fn ($get) => $get('region') ? 'Select plan' : 'Select plan first')
                                ->searchable(),
                        ])
                        ->visible(fn ($get) => $get('provider') !== ServerProvider::CUSTOM),
                    TextInput::make('public_key')
                        ->label('Public Key')
                        ->default($publicKey)
                        ->suffixAction(
                            Action::make('copy')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->tooltip('Copy')
                                ->action(function ($livewire, $state) {
                                    $livewire->js(
                                        'window.navigator.clipboard.writeText("'.$state.'");'
                                    );
                                    Notification::make()
                                        ->success()
                                        ->title('Copied!')
                                        ->send();
                                })
                        )
                        ->helperText('Run this command on your server as root user')
                        ->disabled()
                        ->visible(fn ($get) => $get('provider') === ServerProvider::CUSTOM),
                    TextInput::make('name')
                        ->label('Name')
                        ->rules(fn ($get) => CreateServerAction::rules($get())['name']),
                    Grid::make()
                        ->schema([
                            TextInput::make('ip')
                                ->label('SSH IP Address')
                                ->rules(fn ($get) => CreateServerAction::rules($get())['ip']),
                            TextInput::make('port')
                                ->label('SSH Port')
                                ->rules(fn ($get) => CreateServerAction::rules($get())['port']),
                        ])
                        ->visible(fn ($get) => $get('provider') === ServerProvider::CUSTOM),
                    Grid::make()
                        ->schema([
                            Select::make('os')
                                ->label('OS')
                                ->native(false)
                                ->rules(fn ($get) => CreateServerAction::rules($get())['os'])
                                ->options(
                                    collect(config('core.operating_systems'))
                                        ->mapWithKeys(fn ($value) => [$value => $value])
                                ),
                            Select::make('type')
                                ->label('Server Type')
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->rules(fn ($get) => CreateServerAction::rules($get())['type'])
                                ->options(
                                    collect(config('core.server_types'))
                                        ->mapWithKeys(fn ($value) => [$value => $value])
                                )
                                ->default(ServerType::REGULAR),
                        ]),
                    Grid::make(3)
                        ->schema([
                            Select::make('webserver')
                                ->label('Webserver')
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->rules(fn ($get) => CreateServerAction::rules($get())['webserver'] ?? [])
                                ->options(
                                    collect(config('core.webservers'))->mapWithKeys(fn ($value) => [$value => $value])
                                ),
                            Select::make('database')
                                ->label('Database')
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->rules(fn ($get) => CreateServerAction::rules($get())['database'] ?? [])
                                ->options(
                                    collect(config('core.databases_name'))
                                        ->mapWithKeys(fn ($value, $key) => [
                                            $key => $value.' '.config('core.databases_version')[$key],
                                        ])
                                ),
                            Select::make('php')
                                ->label('PHP')
                                ->native(false)
                                ->selectablePlaceholder(false)
                                ->rules(fn ($get) => CreateServerAction::rules($get())['php'] ?? [])
                                ->options(
                                    collect(config('core.php_versions'))
                                        ->mapWithKeys(fn ($value) => [$value => $value])
                                ),
                        ]),
                ])
                ->modalSubmitActionLabel('Create')
                ->action(function (array $data) {
                    run_action($this, function () use ($data) {
                        $server = app(CreateServerAction::class)->create(auth()->user(), $data);

                        $this->redirect(View::getUrl(['server' => $server]));
                    });
                }),
        ];
    }
}
