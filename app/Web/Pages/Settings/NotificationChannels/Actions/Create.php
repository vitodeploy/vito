<?php

namespace App\Web\Pages\Settings\NotificationChannels\Actions;

use App\Actions\NotificationChannels\AddChannel;
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
                    collect(config('core.notification_channels_providers'))
                        ->mapWithKeys(fn ($provider) => [$provider => $provider])
                )
                ->live()
                ->reactive()
                ->rules(fn (Get $get) => AddChannel::rules($get())['provider']),
            TextInput::make('label')
                ->rules(fn (Get $get) => AddChannel::rules($get())['label']),
            TextInput::make('webhook_url')
                ->label('Webhook URL')
                ->validationAttribute('Webhook URL')
                ->visible(fn (Get $get) => AddChannel::rules($get())['webhook_url'] ?? false)
                ->rules(fn (Get $get) => AddChannel::rules($get())['webhook_url']),
            TextInput::make('email')
                ->visible(fn (Get $get) => AddChannel::rules($get())['email'] ?? false)
                ->rules(fn (Get $get) => AddChannel::rules($get())['email']),
            TextInput::make('bot_token')
                ->label('Bot Token')
                ->visible(fn (Get $get) => AddChannel::rules($get())['bot_token'] ?? false)
                ->rules(fn (Get $get) => AddChannel::rules($get())['bot_token']),
            TextInput::make('chat_id')
                ->label('Chat ID')
                ->visible(fn (Get $get) => AddChannel::rules($get())['chat_id'] ?? false)
                ->rules(fn (Get $get) => AddChannel::rules($get())['chat_id']),
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
            app(AddChannel::class)->add(auth()->user(), $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
