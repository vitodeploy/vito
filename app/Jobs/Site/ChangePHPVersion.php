<?php

namespace App\Jobs\Site;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Site;

class ChangePHPVersion extends Job
{
    protected Site $site;

    protected string $version;

    public function __construct(Site $site, $version)
    {
        $this->site = $site;
        $this->version = $version;
    }

    public function handle(): void
    {
        $this->site->php_version = $this->version;
        $this->site->server->webserver()->handler()->changePHPVersion($this->site, $this->version);
        $this->site->save();
        event(
            new Broadcast('change-site-php-finished', [
                'id' => $this->site->id,
                'php_version' => $this->site->php_version,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('change-site-php-failed', [
                'message' => __('Failed to change PHP!'),
                'id' => $this->site->id,
            ])
        );
    }
}
