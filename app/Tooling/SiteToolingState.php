<?php

namespace App\Tooling;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\IsolatedUser;
use App\Models\Site;

final class SiteToolingState
{
    public const string STATUS_INSTALLING = 'installing';

    public const string STATUS_UNINSTALLING = 'uninstalling';

    public const string STATUS_INSTALL_FAILED = 'install_failed';

    public const string STATUS_UNINSTALL_FAILED = 'uninstall_failed';

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
