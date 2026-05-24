<?php

namespace App\Actions\Site;

use App\Models\IsolatedUser;
use App\Models\Server;
use App\Tooling\ToolingRegistry;

class GetIsolatedUsers
{
    /**
     * @return array<int, array{user: string, sites_count: int, runtime_versions: array<string, string|null>}>
     */
    public function get(Server $server): array
    {
        $toolIds = ToolingRegistry::ids();

        return $server->isolatedUsers()
            ->withCount('sites')
            ->orderBy('username')
            ->get()
            ->map(function (IsolatedUser $iuser) use ($toolIds): array {
                $versions = [];
                foreach ($toolIds as $toolId) {
                    $version = $iuser->toolingVersion($toolId);
                    $versions[$toolId] = $version === 'none' ? null : $version;
                }

                return [
                    'user' => $iuser->username,
                    'sites_count' => (int) $iuser->sites_count,
                    'runtime_versions' => $versions,
                ];
            })
            ->values()
            ->all();
    }
}
