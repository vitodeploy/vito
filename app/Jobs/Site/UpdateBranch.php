<?php

namespace App\Jobs\Site;

use App\Jobs\Job;
use App\Models\Site;
use App\SSHCommands\Website\UpdateBranchCommand;
use Throwable;

class UpdateBranch extends Job
{
    protected Site $site;

    protected string $branch;

    public function __construct(Site $site, string $branch)
    {
        $this->site = $site;
        $this->branch = $branch;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->site->server->ssh()->exec(
            new UpdateBranchCommand(
                $this->site->path,
                $this->branch
            ),
            'update-branch',
            $this->site->id
        );
        $this->site->branch = $this->branch;
        $this->site->save();
    }

    public function failed(): void
    {
    }
}
