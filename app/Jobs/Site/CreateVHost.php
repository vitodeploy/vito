<?php

namespace App\Jobs\Site;

use App\Jobs\Job;
use App\Models\Site;

class CreateVHost extends Job
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    public function handle(): void
    {
        $this->site->server->webserver()->handler()->createVHost($this->site);
    }
}
