<?php

namespace App\Web\Resources\ServerProvider\Pages;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Enums\ServerProvider;
use App\Web\Resources\ServerProvider\ServerProviderResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListServerProviders extends ListRecords
{
    protected static string $resource = ServerProviderResource::class;

    public static function createAction(): Action
    {
        return CreateAction::make()
            ->label('Connect')
            ->modalHeading('Connect to a Server Provider')
            ->form(function (Form $form) {
                return $form
                    ->schema([
                        Select::make('provider')
                            ->options(
                                collect(config('core.server_providers'))
                                    ->filter(fn ($provider) => $provider != ServerProvider::CUSTOM)
                                    ->mapWithKeys(fn ($provider) => [$provider => $provider])
                            )
                            ->live()
                            ->reactive()
                            ->rules(CreateServerProvider::rules()['provider']),
                        TextInput::make('name')
                            ->rules(CreateServerProvider::rules()['name']),
                        TextInput::make('token')
                            ->label('API Key')
                            ->validationAttribute('API Key')
                            ->visible(fn ($get) => in_array($get('provider'), [
                                ServerProvider::DIGITALOCEAN,
                                ServerProvider::LINODE,
                                ServerProvider::VULTR,
                                ServerProvider::HETZNER,
                            ]))
                            ->rules(fn ($get) => CreateServerProvider::providerRules($get())['token']),
                        TextInput::make('key')
                            ->label('Access Key')
                            ->visible(fn ($get) => $get('provider') == ServerProvider::AWS)
                            ->rules(fn ($get) => CreateServerProvider::providerRules($get())['key']),
                        TextInput::make('secret')
                            ->label('Secret')
                            ->visible(fn ($get) => $get('provider') == ServerProvider::AWS)
                            ->rules(fn ($get) => CreateServerProvider::providerRules($get())['secret']),
                        Checkbox::make('global')
                            ->label('Is Global (Accessible in all projects)'),
                    ]);
            })
            ->modalSubmitActionLabel('Connect')
            ->createAnother(false)
            ->modalWidth(MaxWidth::Large)
            ->using(function (array $data) {
                return app(CreateServerProvider::class)->create(auth()->user(), $data);
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            static::createAction(),
        ];
    }
}
