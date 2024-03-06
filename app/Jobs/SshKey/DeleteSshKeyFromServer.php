<?php

namespace App\Jobs\SshKey;

use App\Jobs\Job;
use App\Models\Server;
use App\Models\SshKey;
use App\SSHCommands\System\DeleteSshKeyCommand;
use Throwable;

class DeleteSshKeyFromServer extends Job
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
    public function handle()
    {
        $this->server->ssh()->exec(
            new DeleteSshKeyCommand($this->sshKey->public_key),
            'delete-ssh-key'
        );
        $this->server->sshKeys()->detach($this->sshKey);
    }

    public function failed(): void
    {
        $this->server->sshKeys()->attach($this->sshKey);
    }
}
