<?php

namespace App\Actions\Site;

use App\Models\Server;
use Illuminate\Support\Collection;

class GetIsolatedUsers
{
    /**
     * @return array<int, array{user: string, sites_count: int, node_version: string|null, bun_version: string|null}>
     */
    public function get(Server $server): array
    {
        return $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->get(['user', 'type_data'])
            ->groupBy('user')
            ->map(fn (Collection $sites, string $user): array => [
                'user' => $user,
                'sites_count' => $sites->count(),
                'node_version' => $this->firstVersion($sites, 'node_version'),
                'bun_version' => $this->firstVersion($sites, 'bun_version'),
            ])
            ->values()
            ->all();
    }

    private function firstVersion(Collection $sites, string $key): ?string
    {
        foreach ($sites as $site) {
            $candidate = $site->type_data[$key] ?? null;

            if (is_string($candidate) && $candidate !== '' && $candidate !== 'none') {
                return $candidate;
            }
        }

        return null;
    }
}
