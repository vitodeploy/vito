<?php

namespace App\Web\Pages\Settings\ServerProviders\Actions;

use App\Actions\ServerProvider\EditServerProvider;
use App\Models\ServerProvider;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;

class Edit
{
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->rules(EditServerProvider::rules()['name']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    public static function action(ServerProvider $provider, array $data): void
    {
        app(EditServerProvider::class)->edit($provider, auth()->user(), $data);
    }
}
