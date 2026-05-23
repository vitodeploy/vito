<?php

namespace App\Actions\Site;

use App\Models\Server;
use App\Tooling\ToolingRegistry;
use Illuminate\Support\Collection;

class GetIsolatedUsers
{
    /**
     * @return array<int, array{user: string, sites_count: int, runtime_versions: array<string, string|null>}>
     */
    public function get(Server $server): array
    {
        $toolIds = ToolingRegistry::ids();

        return $server->sites()
            ->where('user', '!=', $server->getSshUser())
            ->get(['user', 'type_data'])
            ->groupBy('user')
            ->map(function (Collection $sites, string $user) use ($toolIds): array {
                $versions = [];
                foreach ($toolIds as $toolId) {
                    $versions[$toolId] = $this->firstVersion($sites, $toolId.'_version');
                }

                return [
                    'user' => $user,
                    'sites_count' => $sites->count(),
                    'runtime_versions' => $versions,
                ];
            })
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
