<?php

namespace App\Support;

class DesktopRuntime
{
    public static function enabled(): bool
    {
        return (bool) config('desktop.enabled', false);
    }

    public static function dataPath(string $path = ''): ?string
    {
        $dataPath = config('desktop.data_path');

        if (! is_string($dataPath) || $dataPath === '') {
            return null;
        }

        return self::joinPath($dataPath, $path);
    }

    public static function storagePath(string $path = ''): ?string
    {
        $storagePath = config('desktop.storage_path');

        if (! is_string($storagePath) || $storagePath === '') {
            return null;
        }

        return self::joinPath($storagePath, $path);
    }

    public static function envPath(string $path = ''): ?string
    {
        $envPath = config('desktop.env_path');

        if (! is_string($envPath) || $envPath === '') {
            return null;
        }

        return self::joinPath($envPath, $path);
    }

    public static function websocketPort(): int
    {
        return (int) config('core.ws_port', 8085);
    }

    public static function websocketUrl(string $path): string
    {
        $appUrl = parse_url(config('app.ws_url') ?: config('app.url'));
        $isSecure = ($appUrl['scheme'] ?? 'http') === 'https';
        $wsProtocol = $isSecure ? 'wss' : 'ws';
        $host = $appUrl['host'] ?? 'localhost';
        $port = $appUrl['port'] ?? ($isSecure ? 443 : 80);
        $path = str_starts_with($path, '/') ? $path : "/{$path}";

        if ((app()->environment('local') && ! config('app.ws_url')) || self::enabled()) {
            return "{$wsProtocol}://{$host}:".self::websocketPort().$path;
        }

        $portSuffix = (($isSecure && $port == 443) || (! $isSecure && $port == 80)) ? '' : ":{$port}";

        return "{$wsProtocol}://{$host}{$portSuffix}{$path}";
    }

    private static function joinPath(string $basePath, string $path): string
    {
        if ($path === '') {
            return $basePath;
        }

        return rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }
}
