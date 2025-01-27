<?php

namespace App\SSH\Git;

use App\Exceptions\SSHError;
use App\Models\Site;

class Git
{
    /**
     * @throws SSHError
     */
    public function clone(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.git.clone', [
                'host' => str($site->getFullRepositoryUrl())->after('@')->before('-'),
                'repo' => $site->getFullRepositoryUrl(),
                'path' => $site->path,
                'branch' => $site->branch,
                'key' => $site->getSshKeyName(),
            ]),
            'clone-repository',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function checkout(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.git.checkout', [
                'path' => $site->path,
                'branch' => $site->branch,
            ]),
            'checkout-branch',
            $site->id
        );
    }
}
