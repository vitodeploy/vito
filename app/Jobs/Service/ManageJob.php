<?php

namespace App\Jobs\Service;

use App\Enums\ServiceStatus;
use App\Models\ServerLog;
use App\Models\Service;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ManageJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Service $service,
        protected string $action,
        protected ServiceStatus $successStatus,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function () {
            $status = $this->service->server->systemd()->{$this->action}($this->service->handler()->unit());
            if (str($status)->contains($this->getExpectedActiveState())) {
                $this->service->status = $this->successStatus;
            } else {
                $this->service->status = ServiceStatus::FAILED;
            }
            $this->service->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();
        ServerLog::log($this->service->server, "{$this->action}-service-failed", $e->getMessage());
    }

    private function getExpectedActiveState(): string
    {
        return in_array($this->action, ['stop', 'disable']) ? 'Active: inactive' : 'Active: active';
    }
}
