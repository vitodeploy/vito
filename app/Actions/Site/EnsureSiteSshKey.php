<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;

class EnsureSiteSshKey
{
    /**
     * @throws SSHError
     */
    public function ensure(Site $site): string
    {
        if ($site->ssh_key) {
            return $site->ssh_key;
        }

        $os = $site->server->os();
        $keyName = $site->getSshKeyName();
        $os->generateSSHKey($keyName, $site);
        $publicKey = $os->readSSHKey($keyName, $site);

        if (str_starts_with($keyName, 'iuser_') && $site->isolatedUser) {
            $site->isolatedUser->ssh_key = $publicKey;
            $site->isolatedUser->save();
            $site->setRelation('isolatedUser', $site->isolatedUser->fresh());
        } else {
            $site->ssh_key = $publicKey;
            $site->save();
        }

        return $publicKey;
    }
}
