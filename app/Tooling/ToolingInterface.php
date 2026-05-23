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
     * Directories to prepend to PATH for commands run as the site's isolated
     * user. Only called when `installedVersion($site)` is non-null — the
     * implementation may assume the tool is installed for the site.
     *
     * @return array<int, string>
     */
    public function pathContributions(Site $site): array;

    /**
     * Shell command names that this tool installs (e.g. `['node','npm','npx']`).
     * Used to decide whether a worker's `command` references this tool.
     *
     * @return array<int, string>
     */
    public static function commands(): array;
}
