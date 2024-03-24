<?php

namespace App\Actions\SshKey;

use App\Enums\SshKeyStatus;
use App\Models\Server;
use App\Models\SshKey;

class DeleteKeyFromServer
{
    public function delete(Server $server, SshKey $sshKey): void
    {
        $sshKey->servers()->updateExistingPivot($server->id, [
            'status' => SshKeyStatus::DELETING,
        ]);
        $server->os()->deleteSSHKey($sshKey->public_key);
        $server->sshKeys()->detach($sshKey);
    }
}
