<?php

namespace App\Actions\Ziggy;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tighten\Ziggy\Ziggy;

final class GetZiggyRoutes
{
    public const string SCRIPT_CACHE_KEY = 'ziggy.routes.script';

    public const string VERSION_CACHE_KEY = 'ziggy.routes.version';

    private ?string $cachedScript = null;

    private ?string $cachedVersion = null;

    public function script(): string
    {
        if ($this->cachedScript !== null) {
            return $this->cachedScript;
        }

        if (! app()->isProduction()) {
            return $this->cachedScript = $this->buildScript();
        }

        return $this->cachedScript = Cache::rememberForever(
            self::SCRIPT_CACHE_KEY,
            fn (): string => $this->buildScript(),
        );
    }

    public function version(): string
    {
        if ($this->cachedVersion !== null) {
            return $this->cachedVersion;
        }

        if (! app()->isProduction()) {
            return $this->cachedVersion = substr(md5($this->script()), 0, 16);
        }

        return $this->cachedVersion = Cache::rememberForever(
            self::VERSION_CACHE_KEY,
            fn (): string => substr(md5($this->script()), 0, 16),
        );
    }

    public function url(): string
    {
        return route('ziggy.routes', ['version' => $this->version()]);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::SCRIPT_CACHE_KEY);
        Cache::forget(self::VERSION_CACHE_KEY);
    }

    private function buildScript(): string
    {
        $payload = (new Ziggy)->toJson();
        $routeFunctionPath = base_path('vendor/tightenco/ziggy/dist/route.umd.js');
        $routeFunction = file_get_contents($routeFunctionPath);

        if ($routeFunction === false) {
            throw new RuntimeException("Failed to read Ziggy route function from {$routeFunctionPath}");
        }

        return "window.Ziggy={$payload};{$routeFunction}";
    }
}
