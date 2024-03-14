<?php

namespace App\SSH\Git;

use App\Models\Site;
use App\SSH\HasScripts;

class Git
{
    use HasScripts;

    public function clone(Site $site): void
    {
        $site->server->ssh()->exec(
            $this->getScript('clone.sh', [
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

    public function checkout(Site $site): void
    {
        $site->server->ssh()->exec(
            $this->getScript('checkout.sh', [
                'path' => $site->path,
                'branch' => $site->branch,
            ]),
            'checkout-branch',
            $site->id
        );
    }
}
