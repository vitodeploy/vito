<?php

namespace App\Jobs\SSL;

use App\Enums\SslStatus;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Site $site, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->site->server_id}", function () {
            /** @var Service $service */
            $service = $this->site->server->webserver();
            /** @var Webserver $webserver */
            $webserver = $service->handler();
            $webserver->setupSSL($this->ssl);
            $this->ssl->status = SslStatus::CREATED;
            $this->ssl->save();
            $webserver->updateVHost($this->site->refresh(), regenerate: [
                'port',
            ]);
        });
    }

    public function failed(Exception $e): void
    {
        $this->ssl->status = SslStatus::FAILED;
        $this->ssl->save();

        ServerLog::log(
            $this->site->server,
            'create-ssl-failed',
            $e->getMessage(),
            $this->site
        );
    }
}
