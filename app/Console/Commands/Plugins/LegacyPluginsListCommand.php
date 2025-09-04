<?php

namespace App\Console\Commands\Plugins;

use App\Facades\Plugins;
use Illuminate\Console\Command;

class LegacyPluginsListCommand extends Command
{
    protected $signature = 'legacy-plugins:list';

    protected $description = 'List all installed legacy-plugins';

    public function handle(): void
    {
        $this->table(['Name', 'Version'], Plugins::all());
    }
}
