<?php

namespace App\Tooling;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\SiteResource;
use App\Models\IsolatedUser;
use App\Models\Site;

/**
 * Tooling state lives on `isolated_users.installed_tooling` as
 * `{ <tool_id>: { version: '22'|'none', status: 'installing'|... |null } }`.
 *
 * The static API stays `Site`-keyed so controllers / jobs need minimal change.
 * Each mutation broadcasts a `site.updated` event for every site belonging to
 * the iuser — the payload shape (a `SiteResource`) is unchanged.
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
        self::completeInstall($site, $toolId, 'none');
    }

    private static function broadcast(IsolatedUser $iuser): void
    {
        $iuser->loadMissing('server');

        foreach ($iuser->sites()->get() as $site) {
            /** @var Site $site */
            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $iuser->server->project_id,
                type: 'site.updated',
                data: new SiteResource($site),
            ));
        }
    }
}
