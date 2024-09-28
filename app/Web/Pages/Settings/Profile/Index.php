<?php

namespace App\Web\Pages\Settings\Profile;

use App\Web\Components\Page;
use App\Web\Pages\Settings\Profile\Widgets\ProfileInformation;
use App\Web\Pages\Settings\Profile\Widgets\TwoFactor;
use App\Web\Pages\Settings\Profile\Widgets\UpdatePassword;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/profile';

    protected static ?string $title = 'Profile';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 2;

    public function getWidgets(): array
    {
        return [
            [ProfileInformation::class],
            [UpdatePassword::class],
            [TwoFactor::class],
        ];
    }
}
