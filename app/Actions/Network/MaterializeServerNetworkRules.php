<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\FirewallRuleStatus;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Enums\ServerNetworkRuleKind;
use App\Events\SocketEvent;
use App\Models\Network;
use App\Models\NetworkFirewallRule;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerNetworkRule;
use App\Models\Service;
use App\Support\Cidr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterializeServerNetworkRules
{
    /** @var array<int, Collection<int, NetworkServer>> */
    private array $peerCache = [];

    /** @var array<int, Collection<int, NetworkFirewallRule>> */
    private array $ruleCache = [];

    /** @var array<int, bool> */
    private array $deviceCache = [];

    /**
     * Re-materialise every non-LEAVING member's server. A topology change on the
     * network (membership, IP, keys) affects the handshake/source rows on all peers.
     */
    public function forNetwork(Network $network): void
    {
        $network->servers()
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('server')
            ->get()
            ->each(fn (NetworkServer $member) => $this->forServer($member->server));
    }

    /**
     * Reconcile the materialised network-rule rows for a single server against the
     * desired set derived from its current memberships.
     */
    public function forServer(Server $server): void
    {
        $desired = $this->desiredFor($server);
        $applied = $server->firewall() instanceof Service;

        $changed = DB::transaction(function () use ($server, $desired, $applied): bool {
            $existing = $server->networkRules()->get()->keyBy(fn (ServerNetworkRule $row): string => $this->identity(
                $row->network_server_id,
                $row->kind,
                $row->network_firewall_rule_id,
                $row->source,
                $row->mask,
            ));

            $changed = false;

            foreach ($desired as $key => $spec) {
                /** @var ?ServerNetworkRule $row */
                $row = $existing->get($key);

                if (! $row instanceof ServerNetworkRule) {
                    $server->networkRules()->create([
                        ...$spec,
                        'status' => $applied ? FirewallRuleStatus::CREATING : FirewallRuleStatus::READY,
                    ]);
                    $changed = true;

                    continue;
                }

                if ($row->name !== $spec['name']
                    || $row->type !== $spec['type']
                    || $row->protocol !== $spec['protocol']
                    || $row->port !== $spec['port']) {
                    $row->fill([
                        'name' => $spec['name'],
                        'type' => $spec['type'],
                        'protocol' => $spec['protocol'],
                        'port' => $spec['port'],
                        'status' => $applied ? FirewallRuleStatus::UPDATING : FirewallRuleStatus::READY,
                    ])->save();
                    $changed = true;

                    continue;
                }

                if ($row->status === FirewallRuleStatus::DELETING) {
                    $row->status = $applied ? FirewallRuleStatus::CREATING : FirewallRuleStatus::READY;
                    $row->save();
                    $changed = true;
                }
            }

            foreach ($existing as $key => $row) {
                if (isset($desired[$key])) {
                    continue;
                }

                if (! $applied || $row->status === FirewallRuleStatus::CREATING) {
                    $row->delete();
                } else {
                    $row->status = FirewallRuleStatus::DELETING;
                    $row->save();
                }
                $changed = true;
            }

            return $changed;
        });

        if ($changed) {
            $this->broadcast($server);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function desiredFor(Server $server): array
    {
        $memberships = NetworkServer::query()
            ->where('server_id', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('network')
            ->get();

        $this->peerCache = [];
        $this->ruleCache = [];
        $this->deviceCache = [];

        $desired = [];

        foreach ($memberships as $membership) {
            $network = $membership->network;

            if ($network->type === NetworkType::WIREGUARD) {
                foreach ($this->handshakes($network, $server) as $handshake) {
                    $mask = Cidr::hostPrefix($handshake['ip']);
                    $spec = [
                        'network_id' => $network->id,
                        'network_server_id' => $membership->id,
                        'network_firewall_rule_id' => null,
                        'kind' => ServerNetworkRuleKind::HANDSHAKE,
                        'name' => 'WireGuard handshake ('.$handshake['name'].')',
                        'type' => 'allow',
                        'protocol' => 'udp',
                        'port' => (string) $network->port,
                        'source' => $handshake['ip'],
                        'mask' => $mask,
                    ];
                    $desired[$this->identity($membership->id, ServerNetworkRuleKind::HANDSHAKE, null, $handshake['ip'], $mask)] = $spec;
                }

                if ($this->hasDevices($network)) {
                    $desired[$this->identity($membership->id, ServerNetworkRuleKind::HANDSHAKE, null, null, null)] = [
                        'network_id' => $network->id,
                        'network_server_id' => $membership->id,
                        'network_firewall_rule_id' => null,
                        'kind' => ServerNetworkRuleKind::HANDSHAKE,
                        'name' => 'WireGuard handshake (devices)',
                        'type' => 'allow',
                        'protocol' => 'udp',
                        'port' => (string) $network->port,
                        'source' => null,
                        'mask' => null,
                    ];
                }
            }

            $sources = $this->sources($network, $server);

            foreach ($this->firewallRules($network) as $rule) {
                foreach ($sources as $source) {
                    $spec = [
                        'network_id' => $network->id,
                        'network_server_id' => $membership->id,
                        'network_firewall_rule_id' => $rule->id,
                        'kind' => ServerNetworkRuleKind::RULE,
                        'name' => $rule->name,
                        'type' => 'allow',
                        'protocol' => $rule->protocol,
                        'port' => $rule->port,
                        'source' => $source['ip'],
                        'mask' => $source['mask'],
                    ];
                    $desired[$this->identity($membership->id, ServerNetworkRuleKind::RULE, $rule->id, $source['ip'], $source['mask'])] = $spec;
                }
            }
        }

        return $desired;
    }

    /**
     * Sources are interpolated unquoted into the `ufw` blade template, which is a shell
     * script — Blade escapes HTML, not shell metacharacters. Every address is re-checked
     * here so nothing but a literal IP can reach it, whatever wrote the column.
     *
     * @return array<int, array{ip: string, name: string}>
     */
    private function handshakes(Network $network, Server $server): array
    {
        return $this->peers($network, $server)
            ->filter(fn (NetworkServer $peer): bool => Cidr::isValidAddress((string) $peer->server->ip))
            ->map(fn (NetworkServer $peer): array => ['ip' => (string) $peer->server->ip, 'name' => $peer->server->name])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{ip: string, mask: int}>
     */
    private function sources(Network $network, Server $server): array
    {
        if ($network->type !== NetworkType::PROVIDER && $network->cidr !== null && $network->cidr !== '') {
            if (! Cidr::isValid($network->cidr)) {
                return [];
            }

            return [[
                'ip' => Cidr::network($network->cidr),
                'mask' => Cidr::prefix($network->cidr),
            ]];
        }

        $sources = [];

        foreach ($this->peers($network, $server) as $peer) {
            $ip = $peer->server_ip_address_id !== null ? $peer->serverIpAddress?->ip : $peer->ip;

            if ($ip === null || ! Cidr::isValidAddress($ip)) {
                continue;
            }

            $sources[] = ['ip' => $ip, 'mask' => Cidr::hostPrefix($ip)];
        }

        return $sources;
    }

    /**
     * @return Collection<int, NetworkServer>
     */
    private function peers(Network $network, Server $server): Collection
    {
        return $this->peerCache[$network->id] ??= NetworkServer::query()
            ->where('network_id', $network->id)
            ->where('server_id', '!=', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('server', 'serverIpAddress')
            ->get();
    }

    /**
     * @return Collection<int, NetworkFirewallRule>
     */
    private function firewallRules(Network $network): Collection
    {
        return $this->ruleCache[$network->id] ??= $network->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::DELETING)
            ->orderBy('id')
            ->get();
    }

    private function hasDevices(Network $network): bool
    {
        return $this->deviceCache[$network->id] ??= $network->peers()
            ->where('status', '!=', NetworkPeerStatus::DISABLED)
            ->exists();
    }

    private function identity(int $networkServerId, ServerNetworkRuleKind $kind, ?int $ruleId, ?string $source, ?int $mask): string
    {
        return implode('|', [$networkServerId, $kind->value, $ruleId ?? '', $source ?? '', $mask ?? '']);
    }

    private function broadcast(Server $server): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'server-network-rule.updated',
            data: ['server_id' => $server->id],
        ));
    }
}
