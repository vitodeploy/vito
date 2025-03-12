<?php

namespace App\Web\Pages\Settings\ServerProviders\Actions;

use App\Actions\ServerProvider\EditServerProvider;
use App\Models\ServerProvider;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;

class Edit
{
    /**
     * @return array<int, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
    public static function action(ServerProvider $provider, array $data): void
    {
        /** @var User $user */
        $user = auth()->user();

        app(EditServerProvider::class)->edit($provider, $user->currentProject, $data);
    }
}
