<?php

namespace App\Web\Pages\Settings\ServerProviders\Actions;

use App\Actions\ServerProvider\CreateServerProvider;
use App\Enums\ServerProvider;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
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
                ->rules(fn (Get $get) => CreateServerProvider::rules($get())['provider']),
            TextInput::make('name')
                ->rules(fn (Get $get) => CreateServerProvider::rules($get())['name']),
            TextInput::make('token')
                ->label('API Key')
                ->validationAttribute('API Key')
                ->visible(fn ($get) => isset(CreateServerProvider::rules($get())['token']))
                ->rules(fn (Get $get) => CreateServerProvider::rules($get())['token']),
            TextInput::make('key')
                ->label('Access Key')
                ->visible(fn ($get) => isset(CreateServerProvider::rules($get())['key']))
                ->rules(fn (Get $get) => CreateServerProvider::rules($get())['key']),
            TextInput::make('secret')
                ->label('Secret')
                ->visible(fn ($get) => isset(CreateServerProvider::rules($get())['secret']))
                ->rules(fn (Get $get) => CreateServerProvider::rules($get())['secret']),
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
            app(CreateServerProvider::class)->create(auth()->user(), auth()->user()->currentProject, $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
