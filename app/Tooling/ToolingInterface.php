<?php

namespace App\Tooling;

use App\Exceptions\SSHError;
use App\Models\Site;

interface ToolingInterface
{
    public static function id(): string;

    public static function label(): string;

    public static function description(): string;

    /**
     * @return array<int, string>
     */
    public static function supportedVersions(): array;

    /**
     * @return array<int, string>
     */
    public static function supportedVersionsWithNone(): array;

    public static function typeDataKey(): string;

    /**
     * @throws SSHError
     */
    public function install(Site $site, string $version): void;

    /**
     * @throws SSHError
     */
    public function uninstall(Site $site): void;

    public function installedVersion(Site $site): ?string;

    /**
     * @return array<int, string>
     */
    public function pathContributions(Site $site): array;

    /**
     * @return array<int, string>
     */
    public static function commands(): array;
}
