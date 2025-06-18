<?php

namespace App\Console\Commands\Plugins;

use App\Facades\Plugins;
use Illuminate\Console\Command;

class PluginsListCommand extends Command
{
    protected $signature = 'plugins:list';

    protected $description = 'List all installed plugins';

    public function handle(): void
    {
        $this->table(['Name', 'Version'], Plugins::all());
    }
}
