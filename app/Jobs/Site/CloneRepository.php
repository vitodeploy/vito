<?php

namespace App\Jobs\Site;

use App\Jobs\Job;
use App\Models\Site;
use App\SSHCommands\Website\CloneRepositoryCommand;
use Throwable;

class CloneRepository extends Job
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
            new CloneRepositoryCommand(
                $this->site->full_repository_url,
                $this->site->path,
                $this->site->branch,
                $this->site->ssh_key_name
            ),
            'clone-repository',
            $this->site->id
        );
    }
}
