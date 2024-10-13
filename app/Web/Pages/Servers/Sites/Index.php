<?php

namespace App\Web\Pages\Servers\Sites;

use App\Actions\Site\CreateSite;
use App\Enums\SiteType;
use App\Models\Site;
use App\Models\SourceControl;
use App\Web\Pages\Settings\SourceControls\Actions\Create;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Throwable;

class Index extends \App\Web\Pages\Servers\Page
{
    protected static ?string $slug = 'servers/{server}/sites';

    protected static ?string $title = 'Sites';

    public function mount(): void
    {
        $this->authorize('viewAny', [Site::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\SitesList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/create-site.html')
                ->openUrlInNewTab(),
            Action::make('create')
                ->label('Create a Site')
                ->icon('heroicon-o-plus')
                ->authorize(fn () => auth()->user()?->can('create', [Site::class, $this->server]))
                ->modalWidth(MaxWidth::FiveExtraLarge)
                ->slideOver()
                ->form([
                    Select::make('type')
                        ->label('Site Type')
                        ->options(
                            collect(config('core.site_types'))->mapWithKeys(fn ($type) => [$type => $type])
                        )
                        ->reactive()
                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if ($state === SiteType::LARAVEL) {
                                $set('web_directory', 'public');
                            } else {
                                $set('web_directory', '');
                            }
                        })
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['type']),
                    TextInput::make('domain')
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['domain']),
                    TagsInput::make('aliases')
                        ->splitKeys(['Enter', 'Tab', ' ', ','])
                        ->placeholder('Type and press enter to add an alias')
                        ->nestedRecursiveRules(CreateSite::rules($this->server, [])['aliases.*']),
                    Select::make('php_version')
                        ->label('PHP Version')
                        ->options(collect($this->server->installedPHPVersions())->mapWithKeys(fn ($version) => [$version => $version]))
                        ->visible(fn (Get $get) => isset(CreateSite::rules($this->server, $get())['php_version']))
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['php_version']),
                    TextInput::make('web_directory')
                        ->placeholder('For / leave empty')
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['web_directory'])
                        ->visible(fn (Get $get) => isset(CreateSite::rules($this->server, $get())['web_directory']))
                        ->helperText(
                            sprintf(
                                'The relative path of your website from /home/%s/your-domain/',
                                $this->server->ssh_user
                            )
                        ),
                    Select::make('source_control')
                        ->label('Source Control')
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['source_control'])
                        ->options(
                            SourceControl::getByProjectId(auth()->user()->current_project_id)
                                ->pluck('profile', 'id')
                        )
                        ->suffixAction(
                            \Filament\Forms\Components\Actions\Action::make('connect')
                                ->form(Create::form())
                                ->modalHeading('Connect to a source control')
                                ->modalSubmitActionLabel('Connect')
                                ->icon('heroicon-o-wifi')
                                ->tooltip('Connect to a source control')
                                ->modalWidth(MaxWidth::Large)
                                ->authorize(fn () => auth()->user()->can('create', SourceControl::class))
                                ->action(fn (array $data) => Create::action($data))
                        )
                        ->placeholder('Select source control')
                        ->live()
                        ->visible(fn (Get $get) => isset(CreateSite::rules($this->server, $get())['source_control'])),
                    TextInput::make('repository')
                        ->placeholder('organization/repository')
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['repository'])
                        ->visible(fn (Get $get) => isset(CreateSite::rules($this->server, $get())['repository'])),
                    TextInput::make('branch')
                        ->placeholder('main')
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['branch'])
                        ->visible(fn (Get $get) => isset(CreateSite::rules($this->server, $get())['branch'])),
                    Checkbox::make('composer')
                        ->label('Run `composer install --no-dev`')
                        ->default(false)
                        ->visible(fn (Get $get) => isset(CreateSite::rules($this->server, $get())['composer'])),
                    // PHPMyAdmin
                    Select::make('version')
                        ->label('PHPMyAdmin Version')
                        ->validationAttribute('PHPMyAdmin Version')
                        ->options([
                            '5.2.1' => '5.2.1',
                        ])
                        ->visible(fn (Get $get) => $get('type') === SiteType::PHPMYADMIN)
                        ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['version']),
                    // WordPress
                    $this->wordpressFields(),
                ])
                ->action(function (array $data) {
                    $this->authorize('create', [Site::class, $this->server]);

                    $this->validate();

                    try {
                        $site = app(CreateSite::class)->create($this->server, $data);

                        $this->redirect(View::getUrl([
                            'server' => $this->server,
                            'site' => $site,
                        ]));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalSubmitActionLabel('Create Site'),
        ];
    }

    private function wordpressFields(): Component
    {
        return Grid::make()
            ->columns(3)
            ->visible(fn (Get $get) => $get('type') === SiteType::WORDPRESS)
            ->schema([
                TextInput::make('title')
                    ->label('Site Title')
                    ->columnSpan(3)
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['title']),
                TextInput::make('email')
                    ->label('WP Admin Email')
                    ->default(auth()->user()?->email)
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['email']),
                TextInput::make('username')
                    ->label('WP Admin Username')
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['username']),
                TextInput::make('password')
                    ->label('WP Admin Password')
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['password']),
                TextInput::make('database')
                    ->helperText('It will create a database with this name')
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['database']),
                TextInput::make('database_user')
                    ->helperText('It will create a db user with this username')
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['database']),
                TextInput::make('database_password')
                    ->rules(fn (Get $get) => CreateSite::rules($this->server, $get())['database']),
            ]);
    }
}
