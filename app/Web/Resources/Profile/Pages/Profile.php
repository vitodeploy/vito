<?php

namespace App\Web\Resources\Profile\Pages;

use App\Web\Resources\Profile\ProfileResource;
use App\Web\Resources\Profile\Widgets\ProfileInformation;
use App\Web\Resources\Profile\Widgets\TwoFactor;
use App\Web\Resources\Profile\Widgets\UpdatePassword;
use App\Web\Traits\PageHasWidgets;
use Filament\Resources\Pages\Page;

class Profile extends Page
{
    use PageHasWidgets;

    protected static string $resource = ProfileResource::class;

    public function getWidgets(): array
    {
        return [
            [ProfileInformation::class],
            [UpdatePassword::class],
            [TwoFactor::class]
        ];
    }
}
