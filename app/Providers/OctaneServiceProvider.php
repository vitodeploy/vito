<?php

namespace App\Providers;

use App\Actions\Plugins\GetPluginInstance;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;

class OctaneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(RequestTerminated::class, function (): void {
            // Flush plugin instance cache to prevent memory leaks in Octane
            if ($this->app->bound(GetPluginInstance::class)) {
                $this->app->make(GetPluginInstance::class)->clear();
            }
        });
    }
}
