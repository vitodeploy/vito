<?php

namespace App\Jobs\Network;

use App\Actions\Network\RecomputeNetworkStatus;
use App\Enums\NetworkServerStatus;
use App\Models\NetworkServer;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Firewall\Firewall;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ApplyNetworkFirewallJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected NetworkServer $member) {}

    public function handle(): void
    {
        $this->run("server-{$this->member->server_id}", function (): void {
            ServerLog::withNetwork($this->member->network_id, function (): void {
                $service = $this->member->server->firewall();
                if (! $service instanceof Service) {
                    return;
                }

                /** @var Firewall $handler */
                $handler = $service->handler();
                $handler->applyRules();
            });
        });
    }

    public function failed(Throwable $e): void
    {
        NetworkServer::query()
            ->whereKey($this->member->id)
            ->whereIn('status', [
                NetworkServerStatus::ACTIVE,
                NetworkServerStatus::PENDING,
                NetworkServerStatus::UPDATING,
            ])
            ->update(['status' => NetworkServerStatus::FAILED]);

        ServerLog::withNetwork(
            $this->member->network_id,
            fn () => ServerLog::log($this->member->server, 'apply-network-firewall-failed', $e->getMessage()),
        );

        app(RecomputeNetworkStatus::class)->handle($this->member->network);
    }
}
