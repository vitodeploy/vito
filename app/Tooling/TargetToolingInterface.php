<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;

interface TargetToolingInterface
{
    /**
     * @throws SSHError
     */
    public function installForTarget(ToolingTargetDTO $target, string $version): void;

    /**
     * @throws SSHError
     */
    public function uninstallForTarget(ToolingTargetDTO $target): void;

    public function installedVersionForTarget(ToolingTargetDTO $target): ?string;

    /**
     * @return array<int, string>
     */
    public function pathContributionsForTarget(ToolingTargetDTO $target): array;
}
