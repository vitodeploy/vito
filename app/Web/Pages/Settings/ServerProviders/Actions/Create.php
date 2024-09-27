<?php

namespace App\Web\Pages\Settings\ServerProviders\Actions;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Enums\ServerProvider;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class Create
{
    public static function form(): array
    {
        return [
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
                ->visible(function ($get) {
                    return $get('provider') == ServerProvider::AWS;
                })
                ->rules(fn ($get) => CreateServerProvider::providerRules($get())['key']),
            TextInput::make('secret')
                ->label('Secret')
                ->visible(fn ($get) => $get('provider') == ServerProvider::AWS)
                ->rules(fn ($get) => CreateServerProvider::providerRules($get())['secret']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    /**
     * @throws Exception
     */
    public static function action(array $data): void
    {
        try {
            app(CreateServerProvider::class)->create(auth()->user(), $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
