<?php

namespace App\Actions\Site;

use App\Models\Server;

class GetIsolatedUsers
{
    /**
     * @return array<int, array{user: string, sites_count: int, node_version: string|null}>
     */
    public function get(Server $server): array
    {
        $grouped = $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->get(['user', 'type_data'])
            ->groupBy('user');

        $rows = [];

        foreach ($grouped as $user => $group) {
            $nodeVersion = null;

            foreach ($group as $site) {
                $candidate = $site->type_data['node_version'] ?? null;
                if (is_string($candidate) && $candidate !== '' && $candidate !== 'none') {
                    $nodeVersion = $candidate;
                    break;
                }
            }

            $rows[] = [
                'user' => (string) $user,
                'sites_count' => $group->count(),
                'node_version' => $nodeVersion,
            ];
        }

        return $rows;
    }
}
