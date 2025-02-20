<?php

namespace App\Cli;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        'command.migrate',
        'command.migrate.install'
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        $this->app->singleton('command.migrate', function ($app) {
            return new MigrateCommand($app['migrator'], $app[Dispatcher::class]);
        });
        $this->app->singleton('command.migrate.install', function ($app) {
            return new InstallCommand($app['migration.repository']);
        });
    }

    protected function shouldDiscoverCommands(): false
    {
        return false;
    }

    protected function getArtisan(): ?Artisan
    {
        return $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
            ->resolveCommands($this->commands);
    }
}
