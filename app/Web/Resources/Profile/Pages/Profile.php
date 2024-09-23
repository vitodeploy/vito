<?php

namespace App\Web\Resources\Profile\Pages;

use App\Web\Resources\Profile\ProfileResource;
use Filament\Resources\Pages\Page;

class Profile extends Page
{
    protected static string $resource = ProfileResource::class;

    protected static string $view = 'web.resources.profile.pages.index';
}
