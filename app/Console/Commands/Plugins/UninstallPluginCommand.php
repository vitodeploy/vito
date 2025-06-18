<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UninstallPluginCommand extends Command
{
    protected $signature = 'plugins:uninstall {name}';

    protected $description = 'Uninstall a plugin by name';

    public function handle(): void
    {
        $this->info('Uninstalling '.$this->argument('name').'...');

        $pluginPath = storage_path('plugins/'.$this->argument('name'));

        if (! File::exists($pluginPath)) {
            $this->error('Plugin not found: '.$this->argument('name'));

            return;
        }

        $this->info('Removing plugin files...');

        File::deleteDirectory($pluginPath);

        $this->info('Plugin '.$this->argument('name').' uninstalled successfully.');

        Artisan::call('plugins:load');
    }
}
