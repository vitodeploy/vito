<?php

namespace App\Jobs\Redirect;

use App\Enums\RedirectStatus;
use App\Models\Redirect;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Services\Webserver\Webserver;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Site $site, protected Redirect $redirect) {}

    public function handle(): void
    {
        $this->run("server-{$this->site->server_id}", function () {
            /** @var Service $service */
            $service = $this->site->server->webserver();
            /** @var Webserver $webserver */
            $webserver = $service->handler();
            $webserver->updateVHost($this->site, regenerate: [
                'redirects',
            ]);
            $this->redirect->status = RedirectStatus::READY;
            $this->redirect->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->redirect->status = RedirectStatus::FAILED;
        $this->redirect->save();

        ServerLog::log(
            $this->site->server,
            'create-redirect-failed',
            $e->getMessage(),
            $this->site
        );
    }
}
