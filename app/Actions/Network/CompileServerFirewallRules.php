<?php

namespace App\Actions\Network;

use App\Enums\FirewallRuleStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Support\Cidr;
use stdClass;

class CompileServerFirewallRules
{
    /**
     * Network firewall allow-rules for a server, emitted BEFORE the server's own
     * rules. Order (ufw is first-match): WireGuard handshake (always, so the
     * tunnel survives `default deny incoming`), then the network's allow rules
     * scoped to the network source. A network is seeded with an "Allow all" rule
     * (null protocol/port → `allow from <source>`) so it is permissive by
     * default; deleting that rule locks the network down to its explicit allows.
     *
     * @return array<int, stdClass>
     */
    public function forServer(Server $server): array
    {
        $memberships = NetworkServer::query()
            ->where('server_id', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('network')
            ->get();

        $handshakes = [];
        $ruleSpecs = [];

        foreach ($memberships as $membership) {
            $network = $membership->network;

            if ($network->type === NetworkType::WIREGUARD) {
                $handshakes = array_merge($handshakes, $this->handshakeSpecs($network, $server));
            }

            $ruleSpecs = array_merge($ruleSpecs, $this->ruleSpecs($network, $this->sources($network, $server)));
        }

        return array_merge($handshakes, $ruleSpecs);
    }

    /**
     * @return array<int, stdClass>
     */
    private function handshakeSpecs(Network $network, Server $server): array
    {
        return $this->peers($network, $server)
            ->filter(fn (NetworkServer $peer): bool => filled($peer->server->ip))
            ->map(fn (NetworkServer $peer): stdClass => $this->spec(
                'allow',
                $peer->server->ip,
                32,
                'udp',
                (string) $network->port,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{ip: string, mask: int}>  $sources
     * @return array<int, stdClass>
     */
    private function ruleSpecs(Network $network, array $sources): array
    {
        $specs = [];

        $rules = $network->firewallRules()
            ->where('status', '!=', FirewallRuleStatus::DELETING)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            foreach ($sources as $source) {
                $specs[] = $this->spec(
                    'allow',
                    $source['ip'],
                    $source['mask'],
                    $rule->protocol,
                    $rule->port,
                );
            }
        }

        return $specs;
    }

    /**
     * The network source(s) for per-port/catch-all rules: the network CIDR when
     * set, otherwise (provider networks without a CIDR) each other member's
     * private IP.
     *
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
     * @return \Illuminate\Support\Collection<int, NetworkServer>
     */
    private function peers(Network $network, Server $server): \Illuminate\Support\Collection
    {
        return NetworkServer::query()
            ->where('network_id', $network->id)
            ->where('server_id', '!=', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('server', 'serverIpAddress')
            ->get();
    }

    private function spec(string $type, ?string $source, ?int $mask, ?string $protocol, ?string $port): stdClass
    {
        return (object) [
            'type' => $type,
            'source' => $source,
            'mask' => $mask,
            'protocol' => $protocol,
            'port' => $port,
        ];
    }
}
