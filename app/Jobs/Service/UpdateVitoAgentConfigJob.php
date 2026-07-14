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
use Throwable;

class UpdateVitoAgentConfigJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public static function dispatchFor(Service $service): void
    {
        try {
            if ($service->name === VitoAgent::id()) {
                return;
            }

            if (! $service->hasHandler() || ! $service->handler()->canBeManaged()) {
                return;
            }

            $monitoring = $service->server->monitoring();
            if (! $monitoring instanceof Service || $monitoring->name !== VitoAgent::id()) {
                return;
            }

            dispatch(new self($service->server))->onQueue('ssh');
        } catch (Throwable $e) {
            Log::warning('Failed to dispatch vito-agent config update', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
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
