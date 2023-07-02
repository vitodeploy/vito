<?php

namespace App\Jobs\Ssl;

use App\Enums\SiteType;
use App\Enums\SslStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Ssl;

class Deploy extends Job
{
    protected Ssl $ssl;

    public function __construct(Ssl $ssl)
    {
        $this->ssl = $ssl;
    }

    public function handle(): void
    {
        $this->ssl->site->server->webserver()->handler()->setupSSL($this->ssl);
        $this->ssl->status = SslStatus::CREATED;
        $this->ssl->save();
        event(
            new Broadcast('deploy-ssl-finished', [
                'ssl' => $this->ssl,
            ])
        );
        if ($this->ssl->site->type == SiteType::WORDPRESS) {
            $typeData = $this->ssl->site->type_data;
            $typeData['url'] = $this->ssl->site->url;
            $this->ssl->site->type_data = $typeData;
            $this->ssl->site->save();
            $this->ssl->site->type()->edit();
        }
    }

    public function failed(): void
    {
        event(
            new Broadcast('deploy-ssl-failed', [
                'ssl' => $this->ssl,
            ])
        );
        $this->ssl->delete();
    }
}
