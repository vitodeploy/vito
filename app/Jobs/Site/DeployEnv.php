<?php

namespace App\Jobs\Site;

use App\Jobs\Job;
use App\Models\Site;
use App\SSHCommands\System\EditFileCommand;
use Throwable;

class DeployEnv extends Job
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->site->server->ssh()->exec(
            new EditFileCommand(
                $this->site->path.'/.env',
                $this->site->env
            )
        );
    }

    public function failed(): void
    {
    }
}
