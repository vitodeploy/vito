<?php

namespace App\Actions\SourceControl;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\SourceControl;

class SetupDeployKey
{
    /**
     * @return array{public_key: ?string, deploy_key_id: ?string}
     *
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function setup(
        Server $server,
        string $user,
        string $keyName,
        SourceControl $sourceControl,
        string $repository,
        string $title,
        ?string $publicKey = null,
        ?int $siteId = null,
        bool $readOnly = false,
    ): array {
        if ($sourceControl->isGithubApp()) {
            return ['public_key' => null, 'deploy_key_id' => null];
        }

        $publicKey ??= $this->generateKey($server, $user, $keyName, $siteId);

        return [
            'public_key' => $publicKey,
            'deploy_key_id' => $sourceControl->provider()->deployKey($title, $repository, $publicKey, $readOnly),
        ];
    }

    /**
     * @throws SSHError
     */
    public function generateKey(Server $server, string $user, string $keyName, ?int $siteId = null): string
    {
        $os = $server->os();
        $os->generateSSHKey($keyName, $user, $siteId);

        return $os->readSSHKey($keyName, $user, $siteId);
    }
}
