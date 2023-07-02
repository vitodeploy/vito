<?php

namespace App\Jobs\SshKey;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Server;
use App\Models\SshKey;
use App\SSHCommands\DeleteSshKeyCommand;
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
        event(
            new Broadcast('delete-ssh-key-finished', [
                'sshKey' => $this->sshKey,
            ])
        );
    }

    public function failed(): void
    {
        $this->server->sshKeys()->attach($this->sshKey);
        event(
            new Broadcast('delete-ssh-key-failed', [
                'sshKey' => $this->sshKey,
            ])
        );
    }
}
