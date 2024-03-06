<?php

namespace App\SiteTypes;

use App\Contracts\SiteType;
use App\Jobs\Site\DeleteSite;
use App\Models\Site;
use Closure;

abstract class AbstractSiteType implements SiteType
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    public function delete(): void
    {
        dispatch(new DeleteSite($this->site))->onConnection('ssh');
    }

    protected function progress(int $percentage): Closure
    {
        return function () use ($percentage) {
            $this->site->progress = $percentage;
            $this->site->save();
        };
    }
}
