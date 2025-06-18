<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class InstallPluginCommand extends Command
{
    protected $signature = 'plugins:install {url} {--branch=} {--tag=}';

    protected $description = 'Install a plugin from a repository';

    public function handle(): void
    {
        $url = $this->argument('url');
        $branch = $this->option('branch');
        $tag = $this->option('tag');
        $vendor = str($url)->beforeLast('/')->afterLast('/');
        $name = str($url)->afterLast('/');

        $this->info("Installing plugin $name from $url");

        if (is_dir(storage_path("plugins/$vendor/$name"))) {
            $this->info("Removing existing plugin $name");
            File::deleteDirectory(storage_path("plugins/$vendor/$name"));
        }

        $this->info("Cloning plugin $name from $url");
        $command = "git clone $url storage/plugins/$vendor/$name";
        if ($branch) {
            $command .= " --branch $branch";
        }
        if ($tag) {
            $command .= " --tag $tag";
        }
        $command .= ' --single-branch';
        $result = Process::timeout(0)->run($command);
        $this->output->write($result->output());

        $this->info("Plugin $name installed successfully");
        $this->info('Loading plugins...');
        $this->call('plugins:load');
        $this->info('Plugins loaded successfully');
        $this->info("Plugin $name is ready to use");
    }
}
