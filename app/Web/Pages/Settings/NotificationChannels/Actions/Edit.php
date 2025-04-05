<?php

namespace App\Web\Pages\Settings\NotificationChannels\Actions;

use App\Actions\NotificationChannels\EditChannel;
use App\Models\NotificationChannel;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

class Edit
{
    /**
     * @return array<int, mixed>
     */
    public static function form(): array
    {
        return [
            TextInput::make('label')
                ->rules(fn (Get $get) => EditChannel::rules($get())['label']),
            Checkbox::make('global')
                ->label('Is Global (Accessible in all projects)'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function action(NotificationChannel $channel, array $data): void
    {
        /** @var User $user */
        $user = auth()->user();
        app(EditChannel::class)->edit($channel, $user, $data);
    }
}
