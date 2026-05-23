<?php

namespace App\Tooling;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Mise\Mise;

/**
 * Base for tooling installed via Mise (https://mise.jdx.dev). Concrete
 * subclasses (Node, Bun, pnpm, Yarn) just declare their id, label,
 * description and supported versions — Mise handles install/uninstall
 * uniformly through the runtime's plugin name (= the tool id).
 */
abstract class MiseTooling extends AbstractTooling
{
    /**
     * @throws SSHError
     */
    public function install(Site $site, string $version): void
    {
        $mise = new Mise($site->server);
        $mise->ensureInstalled();
        $mise->installRuntime($site, static::id(), $version);
    }

    /**
     * @throws SSHError
     */
    public function uninstall(Site $site): void
    {
        (new Mise($site->server))->uninstallRuntime($site, static::id());
    }
}
