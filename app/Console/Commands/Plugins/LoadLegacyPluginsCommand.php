<?php

namespace App\Console\Commands\Plugins;

use App\Facades\Plugins;
use Exception;
use Illuminate\Console\Command;

/**
 * @deprecated
 */
class LoadLegacyPluginsCommand extends Command
{
    protected $signature = 'plugins:load';

    protected $description = 'Load all legacy-plugins from the storage/plugins directory';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->info('Loading legacy plugins...');

        try {
            Plugins::load();
        } catch (Exception $e) {
            $this->output->error($e->getMessage());

            return;
        }

        Plugins::cleanup();

        $this->info('Legacy plugins loaded successfully.');
    }
}
