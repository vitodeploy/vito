<?php

namespace App\Events;

use App\Models\Server;
use Illuminate\Foundation\Events\Dispatchable;

class SiteDeletedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Server $server,
        public readonly int $siteId,
        public readonly string $domain,
    ) {}
}
