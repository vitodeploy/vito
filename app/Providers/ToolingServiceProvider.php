<?php

namespace App\Providers;

use App\Tooling\BunTooling;
use App\Tooling\ComposerTooling;
use App\Tooling\NodeTooling;
use App\Tooling\PnpmTooling;
use App\Tooling\YarnTooling;
use Illuminate\Support\ServiceProvider;

class ToolingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'tooling.providers' => [
                NodeTooling::class,
                BunTooling::class,
                PnpmTooling::class,
                YarnTooling::class,
                ComposerTooling::class,
            ],
        ]);
    }

    public function boot(): void {}
}
