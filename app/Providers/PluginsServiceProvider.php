<?php

namespace App\Providers;

use App\Actions\Plugins\GetPluginInstance;
use App\Actions\Plugins\LoadPlugins;
use App\Console\Commands\Plugins\InstallLegacyPluginCommand;
use App\Console\Commands\Plugins\LegacyPluginsListCommand;
use App\Console\Commands\Plugins\LoadLegacyPluginsCommand;
use App\Plugins\LegacyPlugins;
use Illuminate\Support\ServiceProvider;

class PluginsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('legacy-plugins', function () {
            return new LegacyPlugins;
        });

        $this->app->scoped(GetPluginInstance::class, function () {
            return new GetPluginInstance;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallLegacyPluginCommand::class,
                LoadLegacyPluginsCommand::class,
                LegacyPluginsListCommand::class,
            ]);
        }

        if (! $this->app->runningInConsole()) {
            $this->app->booted(function () {
                app(LoadPLugins::class)->handle();
            });
        }


    }
}
