<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;

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

    public function installedVersion(ToolingTargetDTO $target): ?string
    {
        $version = $target->isolatedUser?->toolingVersion(static::id());

        return $version === 'none' ? null : $version;
    }

    public function pathContributions(ToolingTargetDTO $target): array
    {
        return [];
    }

    public static function commands(): array
    {
        return [];
    }
}
