<?php

namespace App\Web\Resources\Profile;

use Filament\Resources\Resource;

class ProfileResource extends Resource
{
    protected static ?string $slug = 'profile';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\Profile::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ProfileInformation::class,
            Widgets\UpdatePassword::class,
            Widgets\TwoFactor::class,
        ];
    }
}
