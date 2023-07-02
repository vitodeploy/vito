<?php

namespace App\Jobs\Site;

use App\Exceptions\ComposerInstallFailed;
use App\Jobs\Job;
use App\Models\Site;
use App\SSHCommands\ComposerInstallCommand;
use Throwable;

class ComposerInstall extends Job
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @throws ComposerInstallFailed
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->site->server->ssh()->exec(
            new ComposerInstallCommand(
                $this->site->path
            ),
            'composer-install',
            $this->site->id
        );
    }
}
