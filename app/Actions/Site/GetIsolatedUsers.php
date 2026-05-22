<?php

namespace App\Actions\Site;

use App\Models\Server;

class GetIsolatedUsers
{
    /**
     * @return array<int, array{user: string, sites_count: int, node_version: string|null, bun_version: string|null}>
     */
    public function get(Server $server): array
    {
        $counts = $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->groupBy('user')
            ->selectRaw('user, COUNT(*) as sites_count')
            ->get()
            ->map(fn ($row): array => [
                'user' => (string) $row->getAttribute('user'),
                'sites_count' => (int) $row->getAttribute('sites_count'),
            ]);

        $runtimeSites = $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->where(function ($query): void {
                $query
                    ->where(function ($node): void {
                        $node->whereNotNull('type_data->node_version')
                            ->where('type_data->node_version', '!=', 'none')
                            ->where('type_data->node_version', '!=', '');
                    })
                    ->orWhere(function ($bun): void {
                        $bun->whereNotNull('type_data->bun_version')
                            ->where('type_data->bun_version', '!=', 'none')
                            ->where('type_data->bun_version', '!=', '');
                    });
            })
            ->get(['user', 'type_data']);

        /** @var array<string, array{node: string|null, bun: string|null}> $runtimeByUser */
        $runtimeByUser = [];

        foreach ($runtimeSites as $site) {
            $user = (string) $site->user;
            $entry = $runtimeByUser[$user] ?? ['node' => null, 'bun' => null];

            if ($entry['node'] === null) {
                $candidate = $site->type_data['node_version'] ?? null;
                if (is_string($candidate) && $candidate !== '' && $candidate !== 'none') {
                    $entry['node'] = $candidate;
                }
            }

            if ($entry['bun'] === null) {
                $candidate = $site->type_data['bun_version'] ?? null;
                if (is_string($candidate) && $candidate !== '' && $candidate !== 'none') {
                    $entry['bun'] = $candidate;
                }
            }

            $runtimeByUser[$user] = $entry;
        }

        $rows = [];

        foreach ($counts as $row) {
            $user = $row['user'];
            $runtimes = $runtimeByUser[$user] ?? ['node' => null, 'bun' => null];

            $rows[] = [
                'user' => $user,
                'sites_count' => $row['sites_count'],
                'node_version' => $runtimes['node'],
                'bun_version' => $runtimes['bun'],
            ];
        }

        return $rows;
    }
}
