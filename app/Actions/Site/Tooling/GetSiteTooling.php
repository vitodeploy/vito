<?php

namespace App\Actions\Site\Tooling;

use App\Models\Site;
use App\Tooling\SiteToolingState;
use App\Tooling\ToolingRegistry;

class GetSiteTooling
{
    /**
     * @return array{
     *     isolated_user: string,
     *     sibling_sites: array<int, array{id: int, domain: string, url: string}>,
     *     installed_versions: array<string, string|null>,
     *     tool_statuses: array<string, string|null>,
     *     watch_site_ids: array<int, int>,
     * }
     */
    public function get(Site $site): array
    {
        $site->refresh();
        $site->loadMissing('isolatedUser');

        $siblings = $site->siblingsSharingUser()
            ->get(['id', 'domain', 'server_id'])
            ->map(fn (Site $sibling): array => [
                'id' => $sibling->id,
                'domain' => $sibling->domain,
                'url' => route('application', ['server' => $sibling->server_id, 'site' => $sibling->id]),
            ])
            ->all();

        $installed = [];
        $statuses = [];
        foreach (ToolingRegistry::all() as $id => $tool) {
            $installed[$id] = $tool->installedVersion($site);
            $statuses[$id] = SiteToolingState::currentStatus($site, $id);
        }

        return [
            'isolated_user' => $site->user,
            'sibling_sites' => $siblings,
            'installed_versions' => $installed,
            'tool_statuses' => $statuses,
            'watch_site_ids' => array_merge([$site->id], array_column($siblings, 'id')),
        ];
    }
}
