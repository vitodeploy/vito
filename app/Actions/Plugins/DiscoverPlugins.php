<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use Illuminate\Support\Facades\File;

final readonly class DiscoverPlugins
{
    public function __construct(
        private PluginCache $cache,
    ) {}

    public function handle(): void
    {
        $pluginsPath = app_path('Vito'.DIRECTORY_SEPARATOR.'Plugins');
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
                    'namespace' => 'App\\Vito\\Plugins\\'.$namespace.'\\Plugin',
                ]);
            }
        }

        $plugins->each(function ($plugin) use ($pluginFolders) {
            if (! in_array($plugin->folder, $pluginFolders)) {
                $plugin->delete();
            }
        });

        $this->cache->clear();
    }
}
