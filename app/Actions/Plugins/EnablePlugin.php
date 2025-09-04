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
    ) {}

    /**
     * @throws Exception
     */
    public function handle(Plugin $plugin): void
    {
        $implementation = $this->getImplementation->handle($plugin);
        if ($implementation === null) {
            throw new Exception('Unable to enable the plugin, please check the error logs');
        }

        try {
            $implementation->enable();
        } catch (Throwable $ex) {
            PluginError::createFromException($ex, $plugin);
            throw new Exception('Unable to enable the plugin, please check the error logs');
        }

        $plugin->is_enabled = true;
        $plugin->save();
    }
}
