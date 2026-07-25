<?php

namespace App\Jobs\Network;

use App\Actions\Network\MaterializeServerNetworkRules;
use App\Actions\Network\RecomputeNetworkStatus;
use App\DTOs\SocketEventDTO;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkServerResource;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Firewall\Firewall;
use App\Services\VPN\WireGuard;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncNetworkServerJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected NetworkServer $member, protected bool $teardown = false) {}

    public function handle(): void
    {
        $this->run("server-{$this->member->server_id}", function (): void {
            if (! $this->claim()) {
                return;
            }

            ServerLog::withNetwork($this->member->network_id, function (): void {
                if ($this->teardown) {
                    $this->tearDown();

                    return;
                }

                $this->syncToPresent();
            });
        });
    }

    /**
     * The membership is serialised at dispatch time, so a queued job can outlive the state
     * it was queued for — a removal turns a pending sync stale, and a completed teardown
     * leaves no row at all. Re-read under the per-server queue lock and only proceed while
     * the membership still holds the status this job was dispatched to act on.
     */
    private function claim(): bool
    {
        $fresh = NetworkServer::query()->whereKey($this->member->id)->first();

        if (! $fresh instanceof NetworkServer) {
            return false;
        }

        $expected = $this->teardown ? NetworkServerStatus::LEAVING : NetworkServerStatus::UPDATING;

        if ($fresh->status !== $expected) {
            return false;
        }

        $this->member = $fresh;

        return true;
    }

    public function failed(Throwable $e): void
    {
        ServerLog::withNetwork($this->member->network_id, function () use ($e): void {
            if ($this->teardown) {
                ServerLog::log($this->member->server, 'network-teardown-failed', $e->getMessage());
                app(RecomputeNetworkStatus::class)->handle($this->member->network);

                return;
            }

            $failed = NetworkServer::query()
                ->whereKey($this->member->id)
                ->whereIn('status', [
                    NetworkServerStatus::ACTIVE,
                    NetworkServerStatus::PENDING,
                    NetworkServerStatus::UPDATING,
                ])
                ->update(['status' => NetworkServerStatus::FAILED]);

            if ($failed === 1) {
                $this->broadcastMember();
            }

            ServerLog::log($this->member->server, 'network-sync-failed', $e->getMessage());

            app(RecomputeNetworkStatus::class)->handle($this->member->network);
        });
    }

    private function syncToPresent(): void
    {
        $network = $this->member->network;

        if ($network->type === NetworkType::WIREGUARD) {
            /** @var WireGuard $handler */
            $handler = $this->wireGuardService()->handler();
            $handler->configureNetwork($this->member);
        }

        $this->applyFirewall();

        $this->member->status = NetworkServerStatus::ACTIVE;
        $this->member->sync_attempts = 0;
        $this->member->save();

        $this->broadcastMember();
        app(RecomputeNetworkStatus::class)->handle($network);
    }

    private function tearDown(): void
    {
        $network = $this->member->network;
        $server = $this->member->server;

        if ($network->type === NetworkType::WIREGUARD) {
            $service = $server->service(WireGuard::type());
            if ($service instanceof Service) {
                /** @var WireGuard $handler */
                $handler = $service->handler();
                $handler->removeNetwork($network);
            }

            $this->maybeUninstallWireGuard($server);
        }

        $this->applyFirewall();

        $projectId = $network->project_id;
        $memberId = $this->member->id;
        $this->member->delete();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $projectId,
            type: 'network-server.deleted',
            data: ['id' => $memberId],
        ));

        app(RecomputeNetworkStatus::class)->handle($network);
    }

    private function applyFirewall(): void
    {
        app(MaterializeServerNetworkRules::class)->forServer($this->member->server);

        $service = $this->member->server->firewall();
        if (! $service instanceof Service) {
            return;
        }

        /** @var Firewall $handler */
        $handler = $service->handler();
        $handler->applyRules();
    }

    private function maybeUninstallWireGuard(Server $server): void
    {
        $hasOther = NetworkServer::query()
            ->where('server_id', $server->id)
            ->where('id', '!=', $this->member->id)
            ->whereHas('network', fn ($query) => $query->where('type', NetworkType::WIREGUARD))
            ->exists();

        if ($hasOther) {
            return;
        }

        $service = $server->service(WireGuard::type());
        if ($service instanceof Service) {
            $service->handler()->uninstall();
            $service->delete();
        }
    }

    private function wireGuardService(): Service
    {
        $server = $this->member->server;
        $service = $server->service(WireGuard::type());

        if ($service instanceof Service && $service->status === ServiceStatus::READY) {
            return $service;
        }

        if (! $service instanceof Service) {
            /** @var Service $service */
            $service = $server->services()->create([
                'type' => WireGuard::type(),
                'name' => WireGuard::id(),
                'version' => 'latest',
                'status' => ServiceStatus::INSTALLING,
            ]);
        }

        $service->newLog();
        $service->handler()->install();
        $service->status = ServiceStatus::READY;
        $service->installed_version = $service->handler()->version();
        $service->save();

        return $service;
    }

    private function broadcastMember(): void
    {
        $this->member->refresh()->load('server', 'serverIpAddress');

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->member->network->project_id,
            type: 'network-server.updated',
            data: new NetworkServerResource($this->member),
        ));
    }
}
