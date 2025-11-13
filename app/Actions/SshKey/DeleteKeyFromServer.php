<?php

namespace App\Actions\SshKey;

use App\Enums\SshKeyStatus;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\SshKey;

class DeleteKeyFromServer
{
    /**
     * @throws SSHError
     */
    public function delete(Server $server, SshKey $sshKey): void
    {
        $pivot = $server->sshKeys()->where('ssh_keys.id', $sshKey->id)->first();
        $user = $pivot?->pivot->user ?? $server->getSshUser();

        $sshKey->servers()->updateExistingPivot($server->id, [
            'status' => SshKeyStatus::DELETING,
        ]);
        $server->os()->deleteSSHKey($sshKey->public_key, $user);
        $server->sshKeys()->detach($sshKey);
    }
}
