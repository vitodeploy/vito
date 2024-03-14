<?php

namespace App\Actions\SshKey;

use App\Enums\SshKeyStatus;
use App\Models\Server;
use App\Models\SshKey;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeployKeyToServer
{
    public function deploy(User $user, Server $server, array $input): void
    {
        $this->validate($user, $input);

        /** @var SshKey $sshKey */
        $sshKey = SshKey::findOrFail($input['key_id']);
        $server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDING,
        ]);
        $server->os()->deploySSHKey($sshKey->public_key);
        $sshKey->servers()->updateExistingPivot($server->id, [
            'status' => SshKeyStatus::ADDED,
        ]);
    }

    private function validate(User $user, array $input): void
    {
        Validator::make($input, [
            'key_id' => [
                'required',
                Rule::exists('ssh_keys', 'id')->where('user_id', $user->id),
            ],
        ])->validate();
    }
}
