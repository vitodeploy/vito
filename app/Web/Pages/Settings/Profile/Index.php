<?php

namespace App\Web\Pages\Settings\Profile;

use App\Web\Pages\Settings\Profile\Widgets\ProfileInformation;
use App\Web\Pages\Settings\Profile\Widgets\TwoFactor;
use App\Web\Pages\Settings\Profile\Widgets\UpdatePassword;
use App\Web\Traits\PageHasWidgets;
use Filament\Pages\Page;

class Index extends Page
{
    use PageHasWidgets;

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
