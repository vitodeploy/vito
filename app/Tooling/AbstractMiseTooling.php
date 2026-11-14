<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Mise\Mise;

abstract class AbstractMiseTooling extends AbstractTooling implements TargetToolingInterface
{
    /**
     * @throws SSHError
     */
    public function install(Site $site, string $version): void
    {
        $this->installForTarget(ToolingTargetDTO::forSite($site), $version);
    }

    /**
     * @throws SSHError
     */
    public function installForTarget(ToolingTargetDTO $target, string $version): void
    {
        $mise = new Mise($target->server);
        $mise->ensureInstalled();
        $mise->installRuntimeForTarget($target, static::id(), $version);
    }

    /**
     * @throws SSHError
     */
    public function uninstall(Site $site): void
    {
        $this->uninstallForTarget(ToolingTargetDTO::forSite($site));
    }

    /**
     * @throws SSHError
     */
    public function uninstallForTarget(ToolingTargetDTO $target): void
    {
        new Mise($target->server)->uninstallRuntimeForTarget($target, static::id());
    }

    public function pathContributionsForTarget(ToolingTargetDTO $target): array
    {
        return ['/home/'.$target->user.'/.local/share/mise/shims'];
    }
}
