<?php

namespace App\Actions\Site;

use App\Models\Server;
use App\Models\Site;

class GetIsolatedUsers
{
    /**
     * @return array<int, array{user: string, sites_count: int, node_version: string|null}>
     */
    public function get(Server $server): array
    {
        $rows = [];

        $grouped = $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->get(['user'])
            ->groupBy('user');

        foreach ($grouped as $user => $group) {
            $rows[] = [
                'user' => (string) $user,
                'sites_count' => $group->count(),
                'node_version' => Site::existingNodeVersionForUser($server, (string) $user),
            ];
        }

        return $rows;
    }
}
