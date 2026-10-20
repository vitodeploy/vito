<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainType;
use App\Models\EnvironmentDomain;
use App\Models\HostedDomain;
use App\Models\Server;
use App\Models\Site;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AssertHostAvailable
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(int $serverId, string $field, Closure $callback): mixed
    {
        try {
            return Cache::lock('host-reservations:server-'.$serverId, 600)->block(10, $callback);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([$field => 'Another domain operation on this server is in progress. Please retry.']);
        }
    }

    public function assert(Server $server, string $host, string $field, HostedDomain|EnvironmentDomain|null $ignore = null): void
    {
        $hosts = Site::query()->where('server_id', $server->id)
            ->get(['id', 'domain', 'aliases'])->flatMap(function (Site $site) use ($ignore): array {
                $hosts = $ignore instanceof HostedDomain && $ignore->type === HostedDomainType::PRIMARY && $ignore->site_id === $site->id
                    ? [] : [$site->domain];
                foreach ($site->aliases ?? [] as $alias) {
                    if (! ($ignore instanceof HostedDomain && $ignore->site_id === $site->id && $ignore->domain === $alias)) {
                        $hosts[] = $alias;
                    }
                }

                return $hosts;
            })->merge(HostedDomain::query()
            ->whereHas('site', fn ($query) => $query->where('server_id', $server->id))
            ->when($ignore instanceof HostedDomain, fn ($query) => $query->whereKeyNot($ignore->id))->pluck('domain'))
            ->merge(EnvironmentDomain::query()
                ->whereHas('component', fn ($query) => $query->where('server_id', $server->id))
                ->when($ignore instanceof EnvironmentDomain, fn ($query) => $query->whereKeyNot($ignore->id))->pluck('host'));

        foreach ($hosts as $existing) {
            $existing = strtolower($existing);
            $host = strtolower($host);
            if ($existing === $host
                || (str_starts_with($existing, '*.') && str_ends_with($host, substr($existing, 1)))
                || (str_starts_with($host, '*.') && str_ends_with($existing, substr($host, 1)))) {
                throw ValidationException::withMessages([$field => 'This domain is already in use on this server.']);
            }
        }
    }
}
