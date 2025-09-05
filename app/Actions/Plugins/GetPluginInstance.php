<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use App\Models\PluginError;
use App\Plugins\Interfaces\PluginInterface;
use Exception;
use Throwable;

final class GetPluginInstance
{
    private array $implementations = [];

    public function __construct() {}

    public function handle(Plugin $plugin): ?PluginInterface
    {
        if (array_key_exists($plugin->id, $this->implementations)) {
            return $this->implementations[$plugin->id];
        }

        try {
            $namespace = $plugin->namespace;
            $implementation = new $namespace;

            if (! $implementation instanceof PluginInterface) {
                throw new Exception('Plugin does not implement '.PluginInterface::class);
            }

            $this->implementations[$plugin->id] = $implementation;

            return $implementation;
        } catch (Throwable $ex) {
            PluginError::createFromException($ex, $plugin);
        }

        return null;
    }

    public function clear(): void
    {
        $this->implementations = [];
    }
}
