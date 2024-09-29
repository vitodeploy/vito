<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Actions\Server\CreateServer as CreateServerAction;
use App\Enums\ServerProvider;
use App\Enums\ServerType;
use App\Enums\Webserver;
use App\Models\Server;
use App\Web\Fields\AlertField;
use App\Web\Fields\ProviderField;
use App\Web\Pages\Servers\View;
use App\Web\Pages\Settings\ServerProviders\Actions\Create;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets\Widget;
use Throwable;

class CreateServer extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'web.components.form';

    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    public ?string $provider = ServerProvider::HETZNER;

    public ?string $server_provider = '';

    public ?string $region = '';

    public ?string $plan = '';

    public ?string $public_key = '';

    public ?string $name = '';

    public ?string $ip = '';

    public ?string $port = '';

    public ?string $os = '';

    public ?string $type = ServerType::REGULAR;

    public ?string $webserver = Webserver::NGINX;

    public ?string $database = '';

    public ?string $php = '';

    public function form(Form $form): Form
    {
        $publicKey = __('servers.create.public_key_text', [
            'public_key' => get_public_key_content(),
        ]);

        return $form
            ->schema([
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
                    ->rules(fn ($get) => CreateServerAction::rules($this->all())['provider']),
                AlertField::make('alert')
                    ->warning()
                    ->message(__('servers.create.public_key_warning'))
                    ->visible(fn ($get) => $this->provider === ServerProvider::CUSTOM),
                Select::make('server_provider')
                    ->visible(fn ($get) => $this->provider !== ServerProvider::CUSTOM)
                    ->label('Server provider connection')
                    ->rules(fn ($get) => CreateServerAction::rules($this->all())['server_provider'])
                    ->options(function ($get) {
                        return \App\Models\ServerProvider::getByProjectId(auth()->user()->current_project_id)
                            ->where('provider', $this->provider)
                            ->pluck('profile', 'id');
                    })
                    ->live()
                    ->suffixAction(
                        Action::make('connect')
                            ->form(Create::form())
                            ->modalHeading('Connect to a new server provider')
                            ->modalSubmitActionLabel('Connect')
                            ->icon('heroicon-o-wifi')
                            ->tooltip('Connect to a new server provider')
                            ->modalWidth(MaxWidth::Medium)
                            ->authorize(fn () => auth()->user()->can('create', \App\Models\ServerProvider::class))
                            // TODO: remove this after filament #14319 is fixed
                            ->url(\App\Web\Pages\Settings\ServerProviders\Index::getUrl())
                            ->action(fn (array $data) => Create::action($data))
                    )
                    ->placeholder('Select profile')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->visible(fn ($get) => $this->provider !== ServerProvider::CUSTOM),
                Grid::make()
                    ->schema([
                        Select::make('region')
                            ->label('Region')
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['region'])
                            ->live()
                            ->reactive()
                            ->options(function () {
                                if (! $this->server_provider) {
                                    return [];
                                }

                                return \App\Models\ServerProvider::regions($this->server_provider);
                            })
                            ->loadingMessage('Loading regions...')
                            ->disabled(fn ($get) => ! $this->server_provider)
                            ->placeholder(fn ($get) => $this->server_provider ? 'Select region' : 'Select connection first')
                            ->searchable(),
                        Select::make('plan')
                            ->label('Plan')
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['plan'])
                            ->reactive()
                            ->options(function () {
                                if (! $this->server_provider || ! $this->region) {
                                    return [];
                                }

                                return \App\Models\ServerProvider::plans($this->server_provider, $this->region);
                            })
                            ->loadingMessage('Loading plans...')
                            ->disabled(fn ($get) => ! $this->region)
                            ->placeholder(fn ($get) => $this->region ? 'Select plan' : 'Select plan first')
                            ->searchable(),
                    ])
                    ->visible(fn ($get) => $this->provider !== ServerProvider::CUSTOM),
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
                    ->visible(fn ($get) => $this->provider === ServerProvider::CUSTOM),
                TextInput::make('name')
                    ->label('Name')
                    ->rules(fn ($get) => CreateServerAction::rules($this->all())['name']),
                Grid::make()
                    ->schema([
                        TextInput::make('ip')
                            ->label('SSH IP Address')
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['ip']),
                        TextInput::make('port')
                            ->label('SSH Port')
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['port']),
                    ])
                    ->visible(fn ($get) => $this->provider === ServerProvider::CUSTOM),
                Grid::make()
                    ->schema([
                        Select::make('os')
                            ->label('OS')
                            ->native(false)
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['os'])
                            ->options(
                                collect(config('core.operating_systems'))
                                    ->mapWithKeys(fn ($value) => [$value => $value])
                            ),
                        Select::make('type')
                            ->label('Server Type')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['type'])
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
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['webserver'] ?? [])
                            ->options(
                                collect(config('core.webservers'))->mapWithKeys(fn ($value) => [$value => $value])
                            ),
                        Select::make('database')
                            ->label('Database')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['database'] ?? [])
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
                            ->rules(fn ($get) => CreateServerAction::rules($this->all())['php'] ?? [])
                            ->options(
                                collect(config('core.php_versions'))
                                    ->mapWithKeys(fn ($value) => [$value => $value])
                            ),
                    ]),
                Actions::make([
                    Action::make('create')
                        ->label('Create Server')
                        ->button()
                        ->action(fn () => $this->submit()),
                ]),
            ])
            ->columns(1);
    }

    public function submit(): void
    {
        $this->authorize('create', Server::class);

        $this->validate();

        try {
            $server = app(CreateServerAction::class)->create(auth()->user(), $this->all()['data']);

            $this->redirect(View::getUrl(['server' => $server]));
        } catch (Throwable $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
