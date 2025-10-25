<?php

namespace App\Jobs\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Traits\UniqueQueue;
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

    public function failed(): void
    {
        // force delete if retried.
        if ($this->previousStatus === ServiceStatus::FAILED) {
            $this->service->delete();

            return;
        }

        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();
    }
}
