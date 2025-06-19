<?php

namespace App\Console\Commands\Plugins;

use App\Facades\Plugins;
use Exception;
use Illuminate\Console\Command;

class LoadPluginsCommand extends Command
{
    protected $signature = 'plugins:load';

    protected $description = 'Load all plugins from the storage/plugins directory';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->info('Loading plugins...');

        try {
            Plugins::load();
        } catch (Exception $e) {
            $this->output->error($e->getMessage());

            return;
        }

        Plugins::cleanup();

        $this->info('Plugins loaded successfully.');
    }
}
