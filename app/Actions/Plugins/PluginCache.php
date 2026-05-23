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
            $ids = Cache::get(self::CACHE_KEY);

            if (! $this->isValidIdList($ids)) {
                $ids = Plugin::query()
                    ->where('is_installed', true)
                    ->where('is_enabled', true)
                    ->pluck('id')
                    ->all();

                Cache::forever(self::CACHE_KEY, $ids);
            }

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
        Cache::forever(self::CACHE_KEY, $plugins->pluck('id')->all());
    }

    /**
     * @phpstan-assert-if-true array<int, int> $value
     */
    private function isValidIdList(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $id) {
            if (! is_int($id)) {
                return false;
            }
        }

        return true;
    }
}
