<?php

namespace App\Jobs\Site;

use App\Enums\SiteStatus;
use App\Jobs\Job;
use App\Models\Site;

class DeleteSite extends Job
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    public function handle(): void
    {
        $this->site->server->webserver()->handler()->deleteSite($this->site);
        $this->site->delete();
    }

    public function failed(): void
    {
        $this->site->status = SiteStatus::READY;
        $this->site->save();
    }
}
