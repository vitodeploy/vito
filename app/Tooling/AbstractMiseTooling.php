<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;
use App\SSH\Mise\Mise;

abstract class AbstractMiseTooling extends AbstractTooling
{
    /**
     * @throws SSHError
     */
    public function install(ToolingTargetDTO $target, string $version): void
    {
        $mise = new Mise($target->server);
        $mise->ensureInstalled();
        $mise->installRuntime($target, static::id(), $version);
    }

    /**
     * @throws SSHError
     */
    public function uninstall(ToolingTargetDTO $target): void
    {
        new Mise($target->server)->uninstallRuntime($target, static::id());
    }

    public function pathContributions(ToolingTargetDTO $target): array
    {
        return ['/home/'.$target->user.'/.local/share/mise/shims'];
    }
}
