<?php

namespace App\Web\Pages\Settings\StorageProviders\Actions;

use App\Actions\StorageProvider\EditStorageProvider;
use App\Models\StorageProvider;
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
                ->rules(EditStorageProvider::rules()['name']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function action(StorageProvider $provider, array $data): void
    {
        /** @var User $user */
        $user = auth()->user();

        app(EditStorageProvider::class)->edit($provider, $user->currentProject, $data);
    }
}
