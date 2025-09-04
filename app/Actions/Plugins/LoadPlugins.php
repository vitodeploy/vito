<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use App\Models\PluginError;
use Throwable;

final readonly class LoadPlugins
{
    public function __construct(
        private GetPluginInstance $getInstance,
    ) {}

    public function handle(): void
    {
        $plugins = Plugin::where('is_installed', true)
            ->where('is_enabled', true)
            ->get();

        foreach ($plugins as $plugin) {
            try {
                $instance = $this->getInstance->handle($plugin);
                $instance->register();
                $instance->boot();
            } catch (Throwable $exception) {
                $plugin->is_enabled = false;
                $plugin->save();
                PluginError::createFromException($exception, $plugin);
            }
        }
    }
}
