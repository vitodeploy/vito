<?php

namespace App\Actions\Plugins;

use App\Actions\Bootstrap\GetBootstrap;
use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\Plugin;
use App\Models\PluginError;
use Exception;
use Throwable;

final readonly class DisablePlugin
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
        if (! $plugin->is_enabled) {
            throw new Exception('This plugin is already disabled');
        }

        $implementation = $this->getImplementation->handle($plugin);
        if ($implementation === null) {
            throw new Exception('Unable to disable the plugin, please check the error logs');
        }

        try {
            $implementation->disable();
        } catch (Throwable $ex) {
            PluginError::createFromException($ex, $plugin);
            throw new Exception('Unable to disable the plugin, please check the error logs');
        }

        $plugin->is_enabled = false;
        $plugin->save();

        $this->cache->clear();

        GetBootstrap::forgetVersion();
        $newVersion = app(GetBootstrap::class)->computeVersion();
        SocketEvent::dispatch(new SocketEventDTO(0, 'bootstrap.invalidated', ['version' => $newVersion]));
    }
}
