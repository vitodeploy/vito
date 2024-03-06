<?php

namespace App\Jobs\SshKey;

use App\Enums\SshKeyStatus;
use App\Jobs\Job;
use App\Models\Server;
use App\Models\SshKey;
use App\SSHCommands\System\DeploySshKeyCommand;
use Throwable;

class DeploySshKeyToServer extends Job
{
    protected Server $server;

    protected SshKey $sshKey;

    public function __construct(Server $server, SshKey $sshKey)
    {
        $this->server = $server;
        $this->sshKey = $sshKey;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->server->ssh()->exec(
            new DeploySshKeyCommand($this->sshKey->public_key),
            'deploy-ssh-key'
        );
        $this->sshKey->servers()->updateExistingPivot($this->server->id, [
            'status' => SshKeyStatus::ADDED,
        ]);
    }

    public function failed(): void
    {
        $this->server->sshKeys()->detach($this->sshKey);
    }
}
