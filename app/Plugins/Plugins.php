<?php

namespace App\Plugins;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

class Plugins
{
    /**
     * @return array<array<string, string>>
     *
     * @throws FileNotFoundException
     */
    public function all(): array
    {
        $plugins = [];

        foreach (File::directories(plugins_path()) as $vendorDir) {
            foreach (File::directories($vendorDir) as $pluginDir) {
                $pluginComposer = $pluginDir.'/composer.json';
                if (File::exists($pluginComposer)) {
                    $json = json_decode(File::get($pluginComposer), true);
                    $plugins[] = [
                        'name' => $json['name'] ?? 'Unknown',
                        'version' => $json['version'] ?? 'Unknown',
                    ];
                }
            }
        }

        return $plugins;
    }
}
