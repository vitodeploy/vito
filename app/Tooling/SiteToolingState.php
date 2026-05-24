<?php

namespace App\Tooling;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\IsolatedUser;
use App\Models\Site;

/**
 * Tooling state lives on `isolated_users.installed_tooling` as
 * `{ <tool_id>: { version: '22'|'none', status: 'installing'|... |null } }`.
 *
 * The static API stays `Site`-keyed so controllers / jobs need minimal change.
 * Each mutation emits **one** `isolated-user.tooling-updated` event carrying
 * the iuser id + its current tooling state — frontend pages filter by
 * matching their site's `isolated_user_id`. Avoids the previous per-site
 * fan-out (and the SiteResource serialization required for it).
 */
final class SiteToolingState
{
    public const STATUS_INSTALLING = 'installing';

    public const STATUS_UNINSTALLING = 'uninstalling';

    public const STATUS_INSTALL_FAILED = 'install_failed';

    public const STATUS_UNINSTALL_FAILED = 'uninstall_failed';

    public static function currentStatus(Site $site, string $toolId): ?string
    {
        return $site->isolatedUser?->toolingStatus($toolId);
    }

    public static function setStatus(Site $site, string $toolId, ?string $status): void
    {
        $iuser = $site->isolatedUser;
        if (! $iuser instanceof IsolatedUser) {
            return;
        }

        $iuser->setToolingStatus($toolId, $status);

        self::broadcast($iuser);
    }

    public static function completeInstall(Site $site, string $toolId, string $version): void
    {
        $iuser = $site->isolatedUser;
        if (! $iuser instanceof IsolatedUser) {
            return;
        }

        $iuser->setToolingVersion($toolId, $version);
        $iuser->setToolingStatus($toolId, null);

        self::broadcast($iuser);
    }

    public static function completeUninstall(Site $site, string $toolId): void
    {
        $iuser = $site->isolatedUser;
        if (! $iuser instanceof IsolatedUser) {
            return;
        }

        // Drop the tool key entirely rather than marking `version: 'none'` —
        // keeps `installed_tooling` lean as the registry grows.
        $iuser->clearTooling($toolId);

        self::broadcast($iuser);
    }

    private static function broadcast(IsolatedUser $iuser): void
    {
        $iuser->loadMissing('server');
        $iuser->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $iuser->server->project_id,
            type: 'isolated-user.tooling-updated',
            data: [
                'id' => $iuser->id,
                'installed_tooling' => $iuser->installed_tooling ?? new \stdClass,
            ],
        ));
    }
}
