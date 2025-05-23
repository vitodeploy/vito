<?php

namespace App\Actions\SshKey;

use App\Enums\SshKeyStatus;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\SshKey;
use App\Models\User;
use Illuminate\Validation\Rule;

class DeployKeyToServer
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function deploy(Server $server, array $input): void
    {
        /** @var SshKey $sshKey */
        $sshKey = SshKey::query()->findOrFail($input['key_id']);
        $server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDING,
        ]);
        $server->os()->deploySSHKey($sshKey->public_key);
        $sshKey->servers()->updateExistingPivot($server->id, [
            'status' => SshKeyStatus::ADDED,
        ]);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(User $user, Server $server): array
    {
        return [
            'key_id' => [
                'required',
                Rule::exists('ssh_keys', 'id')->where('user_id', $user->id),
                Rule::unique('server_ssh_keys', 'ssh_key_id')->where('server_id', $server->id),
            ],
        ];
    }
}
