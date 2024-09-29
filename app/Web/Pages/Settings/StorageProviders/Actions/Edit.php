<?php

namespace App\Web\Pages\Settings\StorageProviders\Actions;

use App\Actions\StorageProvider\EditStorageProvider;
use App\Models\StorageProvider;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;

class Edit
{
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->rules(EditStorageProvider::rules()['name']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    public static function action(StorageProvider $provider, array $data): void
    {
        app(EditStorageProvider::class)->edit($provider, auth()->user(), $data);
    }
}
