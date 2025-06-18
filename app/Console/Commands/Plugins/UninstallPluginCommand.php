<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

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

        $this->info('Removing plugin from Composer...');
        $result = Process::timeout(0)
            ->path(base_path())
            ->run('composer remove '.$this->argument('name'));
        $this->output->write($result->output());

        if ($result->exitCode() !== 0) {
            $this->error('Failed to remove plugin from Composer: '.$this->argument('name'));

            return;
        }

        $this->info('Removing plugin files...');

        File::deleteDirectory($pluginPath);

        $this->info('Plugin '.$this->argument('name').' uninstalled successfully.');
    }
}
