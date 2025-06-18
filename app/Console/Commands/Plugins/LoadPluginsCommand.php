<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class LoadPluginsCommand extends Command
{
    protected $signature = 'plugins:load';

    protected $description = 'Load all plugins from the storage/plugins directory';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $basePath = base_path();
        $storagePath = storage_path('plugins');
        $composerJson = base_path('composer.json');
        $composerLock = base_path('composer.lock');

        // Backup composer files
        File::copy($composerJson, $composerJson.'.bak');
        File::copy($composerLock, $composerLock.'.bak');

        foreach (File::directories($storagePath) as $vendorDir) {
            foreach (File::directories($vendorDir) as $pluginDir) {
                $pluginComposer = $pluginDir.'/composer.json';
                if (File::exists($pluginComposer)) {
                    $json = json_decode(File::get($pluginComposer), true);
                    if (isset($json['name'])) {
                        $vendor = $json['name'];
                        $this->info("Requiring plugin: $vendor");
                        $result = Process::timeout(0)->path($basePath)->run("composer require $vendor");
                        $this->output->write($result->output());
                    }
                }
            }
        }

        // Restore composer files
        File::move($composerJson.'.bak', $composerJson);
        File::move($composerLock.'.bak', $composerLock);
    }
}
