<?php

namespace App\Actions\SshKey;

use App\Enums\SshKeyStatus;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\SshKey;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeployKeyToServer
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function deploy(Server $server, SshKey $sshKey, array $input = []): void
    {
        // Set default user for backward compatibility
        if (! isset($input['user'])) {
            $input['user'] = $server->getSshUser();
        }

        $this->validate($server, $input);

        $user = $input['user'];

        $server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDING,
            'user' => $user,
        ]);
        $server->os()->deploySSHKey($sshKey->public_key, $user);
        $sshKey->servers()->updateExistingPivot($server->id, [
            'status' => SshKeyStatus::ADDED,
            'user' => $user,
        ]);
    }

    private function validate(Server $server, array $input): void
    {
        if (empty($input) || ! isset($input['user'])) {
            return;
        }

        $rules = [
            'user' => [
                'required',
                'string',
                Rule::in($server->getSshUsers()),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
