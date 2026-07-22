<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\FirewallRuleStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Enums\ServerNetworkRuleKind;
use App\Events\SocketEvent;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerNetworkRule;
use App\Models\Service;
use App\Support\Cidr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterializeServerNetworkRules
{
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

        DB::transaction(function () use ($server, $desired, $applied): void {
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

            if ($changed) {
                $this->broadcast($server);
            }
        });
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

        $desired = [];

        foreach ($memberships as $membership) {
            $network = $membership->network;

            if ($network->type === NetworkType::WIREGUARD) {
                foreach ($this->handshakes($network, $server) as $handshake) {
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
                        'mask' => 32,
                    ];
                    $desired[$this->identity($membership->id, ServerNetworkRuleKind::HANDSHAKE, null, $handshake['ip'], 32)] = $spec;
                }
            }

            $sources = $this->sources($network, $server);

            foreach ($network->firewallRules()->where('status', '!=', FirewallRuleStatus::DELETING)->orderBy('id')->get() as $rule) {
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
     * @return array<int, array{ip: string, name: string}>
     */
    private function handshakes(Network $network, Server $server): array
    {
        return $this->peers($network, $server)
            ->filter(fn (NetworkServer $peer): bool => filled($peer->server->ip))
            ->map(fn (NetworkServer $peer): array => ['ip' => (string) $peer->server->ip, 'name' => $peer->server->name])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{ip: string, mask: int}>
     */
    private function sources(Network $network, Server $server): array
    {
        if ($network->cidr !== null && $network->cidr !== '') {
            return [[
                'ip' => long2ip(Cidr::base($network->cidr)),
                'mask' => Cidr::prefix($network->cidr),
            ]];
        }

        return $this->peers($network, $server)
            ->filter(fn (NetworkServer $peer): bool => $peer->serverIpAddress !== null)
            ->map(fn (NetworkServer $peer): array => [
                'ip' => (string) $peer->serverIpAddress->ip,
                'mask' => 32,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, NetworkServer>
     */
    private function peers(Network $network, Server $server): Collection
    {
        return NetworkServer::query()
            ->where('network_id', $network->id)
            ->where('server_id', '!=', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('server', 'serverIpAddress')
            ->get();
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
