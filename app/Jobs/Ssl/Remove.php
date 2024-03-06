<?php

namespace App\Jobs\Ssl;

use App\Jobs\Job;
use App\Models\Ssl;

class Remove extends Job
{
    protected Ssl $ssl;

    public function __construct(Ssl $ssl)
    {
        $this->ssl = $ssl;
    }

    public function handle(): void
    {
        $this->ssl->site->server->webserver()->handler()->removeSSL($this->ssl);
        $this->ssl->delete();
    }

    public function failed(): void
    {
        $this->ssl->status = 'failed';
        $this->ssl->save();
    }
}
