<?php

namespace App\Actions\Plugins;

use App\Models\PluginError;
use Throwable;

final readonly class BootPlugins
{
    public function __construct(
        private GetPluginInstance $getInstance,
        private PluginCache $cache,
    ) {}

    public function handle(): void
    {
        $plugins = $this->cache->get();
        $booted = [];

        foreach ($plugins as $plugin) {
            try {
                $instance = $this->getInstance->handle($plugin);
                $instance->boot();
                $booted[] = $plugin;
            } catch (Throwable $exception) {
                $plugin->is_enabled = false;
                $plugin->save();
                PluginError::createFromException($exception, $plugin);
            }
        }

        if (count($booted) < count($plugins)) {
            $this->cache->set(collect($booted));
        }
    }
}
