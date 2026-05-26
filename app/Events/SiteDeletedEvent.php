<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SiteDeletedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $serverId,
        public readonly int $siteId,
        public readonly string $domain,
    ) {}
}
