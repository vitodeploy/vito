<?php

namespace App\Actions\Server\Security;

use App\Jobs\Server\Security\DetectSecurityJob;
use App\Models\Server;

class CheckSecurityState
{
    public function check(Server $server): void
    {
        DetectSecurityJob::dispatch($server);
    }
}
