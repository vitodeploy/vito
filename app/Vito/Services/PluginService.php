<?php

namespace App\Vito\Services;

use App\Models\Plugin;
use App\Models\PluginError;
use App\Vito\Interfaces\PluginInterface;
use Exception;
use File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PluginService
{
    private const string CACHE_KEY = 'plugins.enabled';

    private const int CACHE_TTL = 3600;

    private array $loadedPlugins = [];

    private array $pluginInstances = [];

    public function loadPlugins(): void
    {
        $plugins = $this->getEnabledPlugins();
        $registeredPlugins = [];
        $pluginInstances = [];

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            try {
                $instance = $this->getPluginInstance($plugin);
                $instance->register();
                $registeredPlugins[] = $plugin;
                $pluginInstances[$plugin->id] = $instance;
            } catch (Throwable $exception) {
                $plugin->is_enabled = false;
                $plugin->save();
                PluginError::createFromException($exception, $plugin);
            }
        }

        foreach ($registeredPlugins as $plugin) {
            $instance = $pluginInstances[$plugin->id];
            try {
                $instance->boot();
                $this->loadedPlugins[] = $plugin;
                $this->pluginInstances[$plugin->id] = $instance;
            } catch (Throwable $exception) {
                $plugin->is_enabled = false;
                $plugin->save();
                PluginError::createFromException($exception, $plugin);
            }
        }
    }

    public function enablePlugin(int $id): bool|string
    {
        $plugin = Plugin::findOrFail($id);

        if ($plugin->is_enabled) {
            return "Plugin '{$plugin->name}' is already enabled.";
        }

        if (! $plugin->is_installed) {
            return "Plugin '{$plugin->name}' is not installed.";
        }

        try {
            $instance = $this->getPluginInstance($plugin);
            $instance->activate();

            $plugin->is_enabled = true;
            $plugin->save();
        } catch (Throwable $e) {
            PluginError::createFromException($e, $plugin);

            return "Plugin '{$plugin->name}' could not be activated.";
        }

        $this->clearCache();

        return true;
    }

    public function disablePlugin(int $id): bool|string
    {
        $plugin = Plugin::findOrFail($id);

        if (! $plugin->is_enabled) {
            return "Plugin '{$plugin->name}' is already disabled.";
        }

        try {
            $instance = $this->getPluginInstance($plugin);
            $instance->deactivate();

            $plugin->is_enabled = false;
            $plugin->save();
        } catch (Throwable $e) {
            PluginError::createFromException($e, $plugin);

            return "Plugin '{$plugin->name}' could not be disabled.";
        }

        $this->clearCache();

        return true;
    }

    public function installPlugin(int $id): bool|string
    {
        $plugin = Plugin::findOrFail($id);
        if ($plugin->is_installed) {
            return "Plugin '{$plugin->name}' is already installed.";
        }

        try {
            $instance = $this->getPluginInstance($plugin);
            $instance->install();
            $plugin->is_installed = true;
            $plugin->save();
        } catch (Exception $e) {
            PluginError::createFromException($e, $plugin);

            return "Plugin '{$plugin->name}' could not be installed.";
        }

        return true;
    }

    public function uninstallPlugin(int $id): bool|string
    {
        $plugin = Plugin::findOrFail($id);
        if ($plugin->is_installed) {
            return "Plugin '{$plugin->name}' is already uninstalled.";
        }

        try {
            $instance = $this->getPluginInstance($plugin);
            $instance->uninstall();
            $plugin->is_installed = false;
            $plugin->save();
        } catch (Exception $e) {
            PluginError::createFromException($e, $plugin);

            return "Plugin '{$plugin->name}' could not be uninstalled.";
        }

        return true;
    }

    public function getEnabledPlugins(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Plugin::where('is_enabled', true)->orderBy('priority')->get();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function getPluginInstance(Plugin $plugin): PluginInterface
    {
        $namespace = $plugin->namespace;
        $instance = new $namespace;
        if (! $instance instanceof PluginInterface) {
            throw new Exception('Plugins must implement PluginInterface');
        }

        return $instance;
    }
}
