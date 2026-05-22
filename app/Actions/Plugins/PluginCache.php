<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class PluginCache
{
    public function __construct() {}

    private const string CACHE_KEY = 'active-plugins';

    /**
     * Retrieves active plugins
     *
     * @return Collection<int, Plugin>
     */
    public function get(): Collection
    {
        try {
            $ids = Cache::rememberForever(self::CACHE_KEY, function (): array {
                return Plugin::query()
                    ->where('is_installed', true)
                    ->where('is_enabled', true)
                    ->pluck('id')
                    ->all();
            });

            return Plugin::query()->whereIn('id', $ids)->get();
        } catch (Throwable) {
            return collect();
        }
    }

    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  Collection<int, Plugin>  $plugins
     */
    public function set(Collection $plugins): void
    {
        Cache::set(
            key: self::CACHE_KEY,
            value: $plugins->pluck('id')->all(),
        );
    }
}
