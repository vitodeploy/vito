<?php

namespace App\Actions\Service;

use App\Jobs\Service\RefreshServicesJob;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

class RefreshServices
{
    public function refresh(Server $server): bool
    {
        if (! Cache::add(self::flagKey($server), true, self::flagTtl($server))) {
            return false;
        }

        dispatch(new RefreshServicesJob($server))->onQueue('ssh');

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
