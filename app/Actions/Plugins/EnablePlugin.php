<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use App\Models\PluginError;
use Exception;
use Throwable;

final readonly class EnablePlugin
{
    public function __construct(
        private GetPluginInstance $getImplementation,
        private PluginCache $cache,
    ) {}

    /**
     * @throws Exception
     */
    public function handle(Plugin $plugin): void
    {
        if ($plugin->is_enabled) {
            throw new Exception('This plugin is already enabled');
        }

        $implementation = $this->getImplementation->handle($plugin);
        if ($implementation === null) {
            throw new Exception('Unable to enable the plugin, please check the error logs');
        }

        try {
            $plugin->name = $implementation->getName();
            $plugin->description = $implementation->getDescription();
            $implementation->enable();
        } catch (Throwable $ex) {
            PluginError::createFromException($ex, $plugin);
            throw new Exception('Unable to enable the plugin, please check the error logs');
        }

        // FIXME: When plguin is faulty, it still returns successful response to the frontend

        $plugin->is_enabled = true;
        $plugin->save();

        $this->cache->clear();
    }
}
