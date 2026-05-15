<?php

namespace App\Actions\Site;

use App\Models\Server;
use Illuminate\Support\Collection;

class GetIsolatedUsers
{
    /**
     * @return Collection<int, array{user: string, sites_count: int}>
     */
    public function get(Server $server): Collection
    {
        return $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->get(['user'])
            ->groupBy('user')
            ->map(fn ($group, $user) => [
                'user' => (string) $user,
                'sites_count' => $group->count(),
            ])
            ->values();
    }
}
