<?php

namespace App\Jobs\Service;

use App\Enums\ServiceStatus;
use App\Models\ServerLog;
use App\Models\Service;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UninstallJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Service $service, protected ServiceStatus $previousStatus) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function () {
            $this->service->handler()->uninstall();
            $this->service->delete();
        });
    }

    public function failed(Exception $e): void
    {
        // force delete if retried.
        if ($this->previousStatus === ServiceStatus::FAILED) {
            $this->service->delete();

            return;
        }

        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();

        ServerLog::log(
            $this->service->server,
            'service-uninstallation-failed',
            $e->getMessage()
        );
    }
}
