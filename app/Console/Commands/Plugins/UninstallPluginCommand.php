<?php

namespace App\Console\Commands\Plugins;

use App\Facades\Plugins;
use Exception;
use Illuminate\Console\Command;

class UninstallPluginCommand extends Command
{
    protected $signature = 'plugins:uninstall {name}';

    protected $description = 'Uninstall a plugin by name';

    public function handle(): void
    {
        $this->info('Uninstalling '.$this->argument('name').'...');

        try {
            Plugins::uninstall($this->argument('name'));
        } catch (Exception $e) {
            $this->output->error($e->getMessage());

            return;
        }

        $this->info('Plugin uninstalled successfully.');
    }
}
