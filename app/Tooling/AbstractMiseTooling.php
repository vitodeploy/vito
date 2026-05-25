<?php

namespace App\Tooling;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Mise\Mise;

abstract class AbstractMiseTooling extends AbstractTooling
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
        new Mise($site->server)->uninstallRuntime($site, static::id());
    }

    public function pathContributions(Site $site): array
    {
        return ['/home/'.$site->user.'/.local/share/mise/shims'];
    }
}
