<?php

namespace App\Vito\Plugins\RichardAnderson\LaravelQueueWorkers\SiteFeatures;

use App\DTOs\Plugins\Button;
use App\DTOs\Plugins\Page;
use App\Plugins\RegisterSiteFeature;

class QueueWorkers
{
    public static string $name = 'queue-workers';

    public function register(): void
    {
        RegisterSiteFeature::make('laravel', self::$name)
            ->registerPage(
                Page::make('Queue Workers')
                    ->description('Adds a new Laravel Queue Workers.')
                    ->header([
                        Button::make('help')
                            ->text('Help')
                            ->href('https://github.com/laravel/laravel')
                            ->target('_blank'),
                    ])
            );
    }
}
