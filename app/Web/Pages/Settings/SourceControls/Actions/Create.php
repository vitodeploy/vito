<?php

namespace App\Web\Pages\Settings\SourceControls\Actions;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Enums\SourceControl;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class Create
{
    /**
     * @return array<int, mixed>
     */
    public static function form(): array
    {
        return [
            Select::make('provider')
                ->options(
                    collect((array) config('core.source_control_providers'))
                        ->mapWithKeys(fn ($provider) => [$provider => $provider])
                )
                ->live()
                ->reactive()
                ->rules(fn (Get $get) => ConnectSourceControl::rules($get())['provider']),
            TextInput::make('name')
                ->rules(fn (Get $get) => ConnectSourceControl::rules($get())['name']),
            TextInput::make('token')
                ->label('API Key')
                ->validationAttribute('API Key')
                ->visible(fn ($get): bool => in_array($get('provider'), [
                    SourceControl::GITHUB,
                    SourceControl::GITLAB,
                ]))
                ->rules(fn (Get $get) => ConnectSourceControl::rules($get())['token']),
            TextInput::make('url')
                ->label('URL (optional)')
                ->visible(fn ($get): bool => $get('provider') == SourceControl::GITLAB)
                ->rules(fn (Get $get) => ConnectSourceControl::rules($get())['url'])
                ->helperText('If you run a self-managed gitlab enter the url here, leave empty to use gitlab.com'),
            TextInput::make('username')
                ->visible(fn ($get): bool => $get('provider') == SourceControl::BITBUCKET)
                ->rules(fn (Get $get) => ConnectSourceControl::rules($get())['username']),
            TextInput::make('password')
                ->visible(fn ($get): bool => $get('provider') == SourceControl::BITBUCKET)
                ->rules(fn (Get $get) => ConnectSourceControl::rules($get())['password']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function action(array $data): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        throw_if($user->currentProject === null);

        try {
            app(ConnectSourceControl::class)->connect($user->currentProject, $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
