<?php

namespace App\Actions\Service;

use App\Jobs\Service\RefreshServicesJob;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RefreshServices
{
    public function refresh(Server $server): bool
    {
        if (! Cache::add(self::flagKey($server), true, self::flagTtl($server))) {
            return false;
        }

        try {
            dispatch(new RefreshServicesJob($server))->onQueue('ssh');
        } catch (Throwable $e) {
            self::clearFlag($server);

            throw $e;
        }

        return true;
    }

    public static function refreshing(Server $server): bool
    {
        return Cache::has(self::flagKey($server));
    }

    public static function clearFlag(Server $server): void
    {
        Cache::forget(self::flagKey($server));
    }

    private static function flagKey(Server $server): string
    {
        return "services-refreshing:{$server->id}";
    }

    private static function flagTtl(Server $server): int
    {
        return max(900, ProbeServices::budget($server) + 300);
    }
}
