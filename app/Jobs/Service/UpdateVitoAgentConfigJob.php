<?php

namespace App\Jobs\Service;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use App\Services\Monitoring\VitoAgent\VitoAgent;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateVitoAgentConfigJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public static function dispatchFor(Service $service): void
    {
        if ($service->name === VitoAgent::id()) {
            return;
        }

        if (! $service->hasHandler() || ! $service->handler()->shouldCheckStatus()) {
            return;
        }

        $monitoring = $service->server->monitoring();
        if (! $monitoring instanceof Service || $monitoring->name !== VitoAgent::id()) {
            return;
        }

        dispatch(new self($service->server))->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $monitoring = $this->server->monitoring();

            if (! $monitoring instanceof Service || $monitoring->status !== ServiceStatus::READY) {
                return;
            }

            $handler = $monitoring->handler();
            if (! $handler instanceof VitoAgent) {
                return;
            }

            $handler->updateConfig();
        });
    }

    public function failed(Exception $e): void
    {
        Log::warning('Failed to update vito-agent config', [
            'server_id' => $this->server->id,
            'error' => $e->getMessage(),
        ]);
    }
}
