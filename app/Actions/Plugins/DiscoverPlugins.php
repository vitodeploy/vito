<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use File;

final readonly class DiscoverPlugins
{
    public function __construct() {}

    public function handle(): void
    {
        $pluginsPath = app_path('Plugins');
        $globPath = implode(DIRECTORY_SEPARATOR, [$pluginsPath, '*', '*']);
        $pluginFolders = collect(File::glob($globPath))
            ->filter(fn ($path) => File::isDirectory($path))
            ->map(fn ($path) => substr($path, strlen($pluginsPath) + 1))
            ->toArray();

        $plugins = Plugin::all();

        foreach ($pluginFolders as $folder) {
            if (! $plugins->contains('folder', $folder)) {
                $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $folder);
                Plugin::create([
                    'folder' => $folder,
                    'namespace' => 'App\\Plugins\\'.$namespace.'\\Plugin',
                ]);
            }
        }
    }
}
