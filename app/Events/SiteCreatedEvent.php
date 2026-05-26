<?php

namespace App\Events;

use App\Models\Site;
use Illuminate\Foundation\Events\Dispatchable;

class SiteCreatedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Site $site,
    ) {}
}
