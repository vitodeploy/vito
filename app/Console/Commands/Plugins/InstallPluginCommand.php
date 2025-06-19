<?php

namespace App\Console\Commands\Plugins;

use App\Facades\Plugins;
use Exception;
use Illuminate\Console\Command;

class InstallPluginCommand extends Command
{
    protected $signature = 'plugins:install {url} {--branch=} {--tag=}';

    protected $description = 'Install a plugin from a repository';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $url = $this->argument('url');
        $branch = $this->option('branch');
        $tag = $this->option('tag');

        $this->info('Installing plugin from '.$url);

        try {
            Plugins::install($url, $branch, $tag);
        } catch (Exception $e) {
            $this->output->error($e->getMessage());

            return;
        }

        $this->info('Plugin installed successfully');
    }
}
