<?php

namespace App\Tooling;

use App\Models\Site;

/**
 * Base for all Tooling implementations. Provides defaults for
 * the type_data key + supported-versions helpers and reads installed
 * version straight out of `Site.type_data`. Install/uninstall are
 * left abstract because they're backend-specific (e.g. Mise vs
 * something else in the future).
 */
abstract class AbstractTooling implements ToolingInterface
{
    public static function typeDataKey(): string
    {
        return static::id().'_version';
    }

    /**
     * @return array<int, string>
     */
    public static function supportedVersionsWithNone(): array
    {
        return array_merge(['none'], static::supportedVersions());
    }

    public function installedVersion(Site $site): ?string
    {
        if ($site->user === '') {
            return null;
        }

        return Site::existingRuntimeVersionForUser($site->server, $site->user, static::id());
    }
}
