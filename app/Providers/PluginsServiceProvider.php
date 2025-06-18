<?php

namespace App\Providers;

use App\Console\Commands\Plugins\InstallPluginCommand;
use App\Console\Commands\Plugins\LoadPluginsCommand;
use App\Console\Commands\Plugins\PluginsListCommand;
use App\Plugins\Plugins;
use Illuminate\Support\ServiceProvider;

class PluginsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('plugins', function () {
            return new Plugins;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPluginCommand::class,
                LoadPluginsCommand::class,
                PluginsListCommand::class,
            ]);
        }
    }
}
