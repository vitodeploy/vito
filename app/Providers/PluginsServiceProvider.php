<?php

namespace App\Providers;

use App\Actions\Plugins\BootPlugins;
use App\Actions\Plugins\GetPluginInstance;
use App\Console\Commands\Plugins\LoadLegacyPluginsCommand;
use App\Plugins\LegacyPlugins;
use App\Plugins\RegisterCommand;
use App\Plugins\RegisterViews;
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
                LoadLegacyPluginsCommand::class,
            ]);
        }

        $this->app->booted(function () {
            app(BootPlugins::class)->handle();

            foreach (RegisterViews::get() as $name => $path) {
                $this->loadViewsFrom($path, $name);
            }

            if ($this->app->runningInConsole()) {
                $commands = RegisterCommand::get();
                if (count($commands) > 0) {
                    $this->commands($commands);
                }
            }
        });
    }
}
