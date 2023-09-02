<?php

namespace App\Jobs\Site;

use App\Jobs\Job;
use App\Models\Site;
use App\SSHCommands\System\GenerateSshKeyCommand;
use App\SSHCommands\System\ReadSshKeyCommand;
use Throwable;

class DeployKey extends Job
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
            new GenerateSshKeyCommand($this->site->ssh_key_name),
            'generate-ssh-key',
            $this->site->id
        );
        $this->site->ssh_key = $this->site->server->ssh()->exec(
            new ReadSshKeyCommand($this->site->ssh_key_name),
            'read-public-key',
            $this->site->id
        );
        $this->site->save();
        $this->site->sourceControl()->provider()->deployKey(
            $this->site->domain.'-key-'.$this->site->id,
            $this->site->repository,
            $this->site->ssh_key
        );
    }
}
