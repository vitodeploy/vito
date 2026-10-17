<?php

namespace App\SSH\OS;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;
use App\Helpers\SiteShellEnvironment;
use App\Models\Site;

class Composer
{
    /**
     * @throws SSHError
     */
    public function installDependencies(Site $site): void
    {
        $site->server->ssh($site->user)
            ->variables(SiteShellEnvironment::collect(ToolingTargetDTO::forSite($site)))
            ->exec(
                view('ssh.composer.composer-install', [
                    'path' => $site->path,
                ]),
                'composer-install',
                $site->id
            );
    }
}
