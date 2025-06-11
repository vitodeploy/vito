<?php

namespace App\SiteFeatures\LaravelOctane;

use App\SiteFeatures\SiteFeature;

class LaravelOctane extends SiteFeature
{
    public function name(): string
    {
        return 'Laravel Octane';
    }

    public function description(): string
    {
        return 'Set up Laravel Octane for your application.';
    }

    public function actions(): array
    {
        return [];
    }
}
