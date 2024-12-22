<?php

namespace App\Web\Pages\Settings\StorageProviders\Actions;

use App\Actions\StorageProvider\CreateStorageProvider;
use App\Enums\StorageProvider;
use App\Web\Components\Link;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
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
                    collect(config('core.storage_providers'))
                        ->mapWithKeys(fn ($provider) => [$provider => $provider])
                )
                ->live()
                ->reactive()
                ->native(false)
                ->rules(fn ($get) => CreateStorageProvider::rules($get())['provider']),
            TextInput::make('name')
                ->rules(fn ($get) => CreateStorageProvider::rules($get())['name']),
            TextInput::make('token')
                ->label('API Token')
                ->validationAttribute('API Token')
                ->visible(fn ($get) => $get('provider') == StorageProvider::DROPBOX)
                ->rules(fn ($get) => CreateStorageProvider::rules($get())['token']),
            Grid::make()
                ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                ->schema([
                    TextInput::make('host')
                        ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['host']),
                    TextInput::make('port')
                        ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['port']),
                    TextInput::make('username')
                        ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['username']),
                    TextInput::make('password')
                        ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['password']),
                    Checkbox::make('ssl')
                        ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['ssl']),
                    Checkbox::make('passive')
                        ->visible(fn ($get) => $get('provider') == StorageProvider::FTP)
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['passive']),
                ]),
            TextInput::make('api_url')
                ->label('API URL')
                ->visible(fn ($get) => $get('provider') == StorageProvider::S3)
                ->rules(fn ($get) => CreateStorageProvider::rules($get())['api_url'])
                ->helperText('Required if you are using an S3 compatible provider like Cloudflare R2'),
            TextInput::make('path')
                ->visible(fn ($get) => in_array($get('provider'), [
                    StorageProvider::S3,
                    StorageProvider::FTP,
                    StorageProvider::LOCAL,
                ]))
                ->rules(fn ($get) => CreateStorageProvider::rules($get())['path'])
                ->helperText(function ($get) {
                    return match ($get('provider')) {
                        StorageProvider::LOCAL => 'The absolute path on your server that the database exists. like `/home/vito/db-backups`',
                        default => '',
                    };
                }),
            Grid::make()
                ->visible(fn ($get) => $get('provider') == StorageProvider::S3)
                ->schema([
                    TextInput::make('key')
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['key'])
                        ->helperText(function ($get) {
                            return match ($get('provider')) {
                                StorageProvider::S3 => new Link(
                                    href: 'https://docs.aws.amazon.com/general/latest/gr/aws-sec-cred-types.html',
                                    text: 'How to generate?',
                                    external: true
                                ),
                                default => '',
                            };
                        }),
                    TextInput::make('secret')
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['secret']),
                    TextInput::make('region')
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['region']),
                    TextInput::make('bucket')
                        ->rules(fn ($get) => CreateStorageProvider::rules($get())['bucket']),
                ]),
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
            app(CreateStorageProvider::class)->create(auth()->user(), auth()->user()->currentProject, $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
