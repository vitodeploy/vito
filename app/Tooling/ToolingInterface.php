<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;

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
    public function install(ToolingTargetDTO $target, string $version): void;

    /**
     * @throws SSHError
     */
    public function uninstall(ToolingTargetDTO $target): void;

    public function installedVersion(ToolingTargetDTO $target): ?string;

    /**
     * @return array<int, string>
     */
    public function pathContributions(ToolingTargetDTO $target): array;

    /**
     * @return array<int, string>
     */
    public static function commands(): array;
}
