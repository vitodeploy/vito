<?php

namespace App\Providers;

use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use Illuminate\Support\ServiceProvider;

class ServerServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RegisterServerFeature::make('motd')
            ->label('MOTD')
            ->description('Manage the message of the day displayed on SSH login.')
            ->register();
        RegisterServerFeatureAction::make('motd', 'enable')
            ->label('Enable')
            ->handler(\App\ServerFeatures\Motd\Enable::class)
            ->register();
        RegisterServerFeatureAction::make('motd', 'disable')
            ->label('Disable')
            ->handler(\App\ServerFeatures\Motd\Disable::class)
            ->register();
    }
}
