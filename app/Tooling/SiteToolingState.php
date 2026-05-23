<?php

namespace App\Tooling;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use App\SiteTypes\Concerns\UsesMiseRuntime;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Per-tool runtime state lives in `sites.type_data`:
 *   - `{tool}_version`: '22', '1.2', ..., or 'none'
 *   - `{tool}_status` : 'installing' | 'uninstalling' | 'install_failed' | 'uninstall_failed' | null
 *
 * `apply()` mutates every sibling site sharing the same isolated user (and only
 * those that use the `UsesMiseRuntime` trait) inside a transaction, then
 * `broadcast()` emits a `site.updated` socket event for each so live clients
 * refresh.
 */
final class SiteToolingState
{
    public const STATUS_INSTALLING = 'installing';

    public const STATUS_UNINSTALLING = 'uninstalling';

    public const STATUS_INSTALL_FAILED = 'install_failed';

    public const STATUS_UNINSTALL_FAILED = 'uninstall_failed';

    public static function statusKey(string $toolId): string
    {
        return $toolId.'_status';
    }

    public static function currentStatus(Site $site, string $toolId): ?string
    {
        $status = $site->type_data[self::statusKey($toolId)] ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }

    /**
     * @param  Closure(Site): void  $mutate
     * @return array<int, Site>
     */
    public static function apply(Site $origin, Closure $mutate): array
    {
        $affected = [];

        DB::transaction(function () use ($origin, $mutate, &$affected): void {
            $sites = $origin->siblingsSharingUser(includeSelf: true)->get();

            foreach ($sites as $site) {
                if (! in_array(UsesMiseRuntime::class, class_uses_recursive($site->type()), true)) {
                    continue;
                }

                $mutate($site);
                $affected[] = $site;
            }
        });

        return $affected;
    }

    /**
     * @param  array<int, Site>  $sites
     */
    public static function broadcast(array $sites): void
    {
        foreach ($sites as $site) {
            $site->refresh();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $site->server->project_id,
                type: 'site.updated',
                data: new SiteResource($site),
            ));
        }
    }

    /**
     * Convenience: set `{tool}_status` (or clear it with null) across all siblings
     * and broadcast.
     */
    public static function setStatus(Site $origin, string $toolId, ?string $status): void
    {
        $key = self::statusKey($toolId);
        $affected = self::apply($origin, function (Site $site) use ($key, $status): void {
            $site->jsonUpdate('type_data', $key, $status);
        });

        self::broadcast($affected);
    }

    /**
     * Convenience: set version + clear status atomically per site, then broadcast.
     */
    public static function completeInstall(Site $origin, string $toolId, string $version): void
    {
        $versionKey = $toolId.'_version';
        $statusKey = self::statusKey($toolId);

        $affected = self::apply($origin, function (Site $site) use ($versionKey, $statusKey, $version): void {
            $data = $site->type_data ?? [];
            $data[$versionKey] = $version;
            $data[$statusKey] = null;
            $site->type_data = $data;
            $site->save();
        });

        self::broadcast($affected);
    }

    public static function completeUninstall(Site $origin, string $toolId): void
    {
        self::completeInstall($origin, $toolId, 'none');
    }
}
