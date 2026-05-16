<?php

namespace App\Actions\Ziggy;

use Illuminate\Support\Facades\Cache;
use Tighten\Ziggy\Ziggy;

final readonly class GetZiggyRoutes
{
    public const string SCRIPT_CACHE_KEY = 'ziggy.routes.script';

    public const string VERSION_CACHE_KEY = 'ziggy.routes.version';

    public function script(): string
    {
        if (! app()->isProduction()) {
            return $this->buildScript();
        }

        return Cache::rememberForever(self::SCRIPT_CACHE_KEY, fn (): string => $this->buildScript());
    }

    public function version(): string
    {
        if (! app()->isProduction()) {
            return substr(md5($this->script()), 0, 16);
        }

        return Cache::rememberForever(
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
        $routeFunction = file_get_contents(base_path('vendor/tightenco/ziggy/dist/route.umd.js'));

        return "window.Ziggy={$payload};{$routeFunction}";
    }
}
