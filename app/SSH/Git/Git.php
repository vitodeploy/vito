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
                'host' => str($site->full_repository_url)->after('@')->before('-'),
                'repo' => $site->full_repository_url,
                'path' => $site->path,
                'branch' => $site->branch,
                'key' => $site->ssh_key_name,
            ]),
            'clone-repository'
        );
    }

    public function checkout(Site $site): void
    {
        $site->server->ssh()->exec(
            $this->getScript('checkout.sh', [
                'path' => $site->path,
                'branch' => $site->branch,
            ]),
            'checkout-branch'
        );
    }
}
