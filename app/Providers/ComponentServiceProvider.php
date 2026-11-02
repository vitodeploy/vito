<?php

namespace App\Providers;

use App\ComponentCapabilities\Scheduler;
use App\ComponentCapabilities\Web;
use App\ComponentCapabilities\Worker;
use App\Plugins\RegisterComponentCapability;
use App\Plugins\RegisterRuntimePreset;
use App\RuntimePresets\Bun;
use App\RuntimePresets\Custom;
use App\RuntimePresets\Laravel;
use App\RuntimePresets\NodeJS;
use App\RuntimePresets\PHP;
use App\RuntimePresets\StaticSite;
use Illuminate\Support\ServiceProvider;

class ComponentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        foreach ([PHP::class, NodeJS::class, Bun::class, StaticSite::class, Custom::class, Laravel::class] as $preset) {
            RegisterRuntimePreset::make($preset::id())
                ->handler($preset)
                ->register();
        }

        foreach ([Web::class, Worker::class, Scheduler::class] as $capability) {
            RegisterComponentCapability::make($capability::id())
                ->handler($capability)
                ->register();
        }
    }
}
