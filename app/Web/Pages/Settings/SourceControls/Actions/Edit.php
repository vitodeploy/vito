<?php

namespace App\Web\Pages\Settings\SourceControls\Actions;

use App\Actions\SourceControl\EditSourceControl;
use App\Models\SourceControl;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class Edit
{
    public static function form(SourceControl $sourceControl): array
    {
        return [
            TextInput::make('name')
                ->rules(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['name']),
            TextInput::make('token')
                ->label('API Key')
                ->validationAttribute('API Key')
                ->visible(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['token'] ?? false)
                ->rules(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['token']),
            TextInput::make('url')
                ->label('URL (optional)')
                ->visible(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['url'] ?? false)
                ->rules(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['url'])
                ->helperText('If you run a self-managed gitlab enter the url here, leave empty to use gitlab.com'),
            TextInput::make('username')
                ->visible(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['username'] ?? false)
                ->rules(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['username']),
            TextInput::make('password')
                ->visible(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['password'] ?? false)
                ->rules(fn (Get $get) => EditSourceControl::rules($sourceControl, $get())['password']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    /**
     * @throws Exception
     */
    public static function action(SourceControl $sourceControl, array $data): void
    {
        try {
            app(EditSourceControl::class)->edit($sourceControl, auth()->user()->currentProject, $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
