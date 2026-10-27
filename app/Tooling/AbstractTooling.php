<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Models\Site;

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
        return $this->installedVersionForTarget(ToolingTargetDTO::forSite($site));
    }

    public function installedVersionForTarget(ToolingTargetDTO $target): ?string
    {
        $version = $target->isolatedUser?->toolingVersion(static::id());

        return $version === 'none' ? null : $version;
    }

    public function pathContributions(Site $site): array
    {
        return $this->pathContributionsForTarget(ToolingTargetDTO::forSite($site));
    }

    /**
     * @return array<int, string>
     */
    public function pathContributionsForTarget(ToolingTargetDTO $target): array
    {
        return [];
    }

    public static function commands(): array
    {
        return [];
    }
}
