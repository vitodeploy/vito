<?php

namespace App\Jobs\ServerIp;

use App\Actions\ServerIp\RefreshServerIps;
use App\DTOs\SocketEventDTO;
use App\Enums\IpAddressStatus;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class PersistServerIpsJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    private const NETPLAN_PATH = '/etc/netplan/60-vito.yaml';

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function (): void {
            app(RefreshServerIps::class)->handle($this->server);

            $managed = $this->server->ipAddresses()
                ->where('is_managed', true)
                ->where('status', '!=', IpAddressStatus::DELETING)
                ->get();

            $hasManaged = $managed->isNotEmpty();

            if ($hasManaged) {
                $this->server->os()->write(
                    self::NETPLAN_PATH,
                    view('ssh.network.netplan', [
                        'interfaces' => $this->buildInterfaces($managed),
                        'managedIps' => $managed->pluck('ip')->values()->all(),
                    ])->render(),
                    'root'
                );
            }

            $this->server->ssh()->exec(
                view('ssh.network.apply-netplan', ['hasManaged' => $hasManaged]),
                'apply-netplan'
            );

            $this->finalize();
        });
    }

    public function failed(Exception $e): void
    {
        $this->server->ipAddresses()
            ->whereIn('status', [IpAddressStatus::CONFIGURING, IpAddressStatus::DELETING])
            ->update(['status' => IpAddressStatus::FAILED]);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'server-ip.updated',
            data: ['server_id' => $this->server->id],
        ));

        ServerLog::log($this->server, 'apply-server-ips-failed', $e->getMessage());
    }

    /**
     * Build the per-interface address map for netplan. For every interface we
     * manage, the interface's existing static (non-dynamic) addresses are
     * re-included so that netplan's same-key override does not drop the
     * provider's primary address (DHCP/SLAAC addresses are excluded — they are
     * supplied by the provider config under a different key).
     *
     * @param  Collection<int, ServerIpAddress>  $managed
     * @return array<string, array<int, string>>
     */
    private function buildInterfaces(Collection $managed): array
    {
        $interfaces = $managed->pluck('interface')->filter()->unique()->values();

        $existingStatic = $this->server->ipAddresses()
            ->where('is_managed', false)
            ->where('is_dynamic', false)
            ->whereIn('interface', $interfaces)
            ->get();

        return $managed->concat($existingStatic)
            ->groupBy('interface')
            ->map(fn (Collection $rows): array => $rows
                ->map(fn (ServerIpAddress $row): string => $row->ip.'/'.$row->prefix_length)
                ->unique()
                ->values()
                ->all())
            ->all();
    }

    private function finalize(): void
    {
        $deleting = $this->server->ipAddresses()
            ->where('status', IpAddressStatus::DELETING)
            ->get();

        DB::transaction(function () use ($deleting): void {
            foreach ($deleting as $address) {
                $address->delete();
            }

            $this->server->ipAddresses()
                ->where('status', IpAddressStatus::CONFIGURING)
                ->update(['status' => IpAddressStatus::CONFIGURED]);
        });

        foreach ($deleting as $address) {
            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->server->project_id,
                type: 'server-ip.deleted',
                data: ['id' => $address->id],
            ));
        }

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'server-ip.updated',
            data: ['server_id' => $this->server->id],
        ));
    }
}
