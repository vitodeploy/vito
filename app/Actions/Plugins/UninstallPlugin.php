<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use App\Models\PluginError;
use Exception;
use File;
use Throwable;

final readonly class UninstallPlugin
{
    public function __construct(
        private GetPluginInstance $getImplementation,
    ) {}

    /**
     * @throws Exception
     */
    public function handle(Plugin $plugin, bool $force = false): void
    {
        $implementation = $this->getImplementation->handle($plugin);
        if ($implementation === null) {
            throw new Exception('Unable to uninstall the plugin, please check the error logs');
        }

        try {
            $implementation->install();
        } catch (Throwable $ex) {
            if (! $force) {
                PluginError::createFromException($ex, $plugin);
                throw new Exception('Unable to uninstall the plugin, please check the error logs');
            }
        }

        $folder = $this->path_join([app_path('Plugins'), $plugin->folder]);
        File::deleteDirectory($folder);

        $subFolder = dirname($folder);
        if (count(File::directories($subFolder)) === 0) {
            File::deleteDirectory($subFolder);
        }

        $plugin->delete();
    }

    public function path_join(array $strings): string
    {
        return implode(DIRECTORY_SEPARATOR, $strings);
    }
}
