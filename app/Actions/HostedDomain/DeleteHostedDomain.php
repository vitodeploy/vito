<?php

namespace App\Actions\HostedDomain;

use App\Models\HostedDomain;

class DeleteHostedDomain
{
    public function delete(HostedDomain $hostedDomain): void
    {
        $hostedDomain->ensureModifiable('delete');

        $site = $hostedDomain->site;

        $hostedDomain->delete();

        $site->webserver()->updateVHost($site);
    }
}
