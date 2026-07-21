<?php

namespace App\Jobs\Network;

use App\Actions\Network\RecomputeNetworkStatus;
use App\Enums\NetworkServerStatus;
use App\Models\NetworkServer;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Firewall\Firewall;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyNetworkFirewallJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected NetworkServer $member) {}

    public function handle(): void
    {
        $this->run("server-{$this->member->server_id}", function (): void {
            $service = $this->member->server->firewall();
            if (! $service instanceof Service) {
                return;
            }

            /** @var Firewall $handler */
            $handler = $service->handler();
            $handler->applyRules();
        });
    }

    public function failed(Exception $e): void
    {
        NetworkServer::query()
            ->whereKey($this->member->id)
            ->where('status', NetworkServerStatus::ACTIVE)
            ->update(['status' => NetworkServerStatus::FAILED]);

        ServerLog::log($this->member->server, 'apply-network-firewall-failed', $e->getMessage());

        app(RecomputeNetworkStatus::class)->handle($this->member->network);
    }
}
